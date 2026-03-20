<?php

namespace App\Http\Controllers;

use App\Mail\TenantRegistrationReceivedMail;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Services\TenantDatabaseProvisioner;
use Stancl\Tenancy\Tenancy;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class TenantController extends Controller
{
    protected function seedTenantRoles(Tenant $tenant): Role
    {
        $permissions = [
            'use pos',
            'create orders',
            'process payments',
            'manage brewing orders',
            'view products',
            'view brewing guides',
            'manage products',
            'view reports',
            'manage users',
            'delete users',
        ];

        $tenantPermissions = collect($permissions)
            ->map(function (string $permissionName) {
                return Permission::on('tenant')->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $ownerRole = Role::on('tenant')->firstOrCreate([
            'name' => 'Owner',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $ownerRole->syncPermissions($tenantPermissions);

        $cashierRole = Role::on('tenant')->firstOrCreate([
            'name' => 'Cashier',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $cashierRole->syncPermissions($tenantPermissions->whereIn('name', [
            'use pos',
            'create orders',
            'process payments',
            'view products',
            'view brewing guides',
        ]));

        return $ownerRole;
    }
    protected function addLocalSubdomainHost(string $subdomain): bool
    {
        if (!app()->environment('local')) {
            return true;
        }

        $domain = config('app.domain');

        if (!$domain || $domain === 'localhost') {
            return false;
        }

        $host = strtolower("{$subdomain}.{$domain}");
        $hostsPath = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'C:\\Windows\\System32\\drivers\\etc\\hosts'
            : '/etc/hosts';

        $content = @file_get_contents($hostsPath);

        if ($content === false) {
            Log::warning('Unable to read hosts file when creating tenant subdomain.', [
                'hosts_path' => $hostsPath,
                'host' => $host,
            ]);
            return false;
        }

        if (preg_match('/^\s*127\.0\.0\.1\s+' . preg_quote($host, '/') . '(\s|$)/mi', $content)) {
            return true;
        }

        $line = PHP_EOL . "127.0.0.1\t{$host}";
        $written = @file_put_contents($hostsPath, $line, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            Log::warning('Unable to append tenant subdomain to hosts file. Try running server as administrator.', [
                'hosts_path' => $hostsPath,
                'host' => $host,
            ]);
            return false;
        }

        return true;
    }

    public function create(Request $request): View
    {
        return view('tenant.create', [
            'paymentCancelled' => $request->query('payment') === 'cancelled',
        ]);
    }

    public function shopLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subdomain' => 'required|alpha_dash|exists:tenants,subdomain',
        ]);

        return redirect()->to(route('tenant.login', ['subdomain' => $data['subdomain']]));
    }

    public function store(Request $request, TenantDatabaseProvisioner $databaseProvisioner): ViewContract|RedirectResponse
    {
        $data = $this->validateTenantRegistrationData($request);
        $monthlyPlanPrice = (float) config('plans.' . $data['plan'] . '.price', 0);
        $totalSubscriptionAmount = $monthlyPlanPrice * $data['subscription_months'];

        if ($data['payment_method'] === 'stripe') {
            return $this->startStripeRegistrationCheckout($request, $data, $totalSubscriptionAmount);
        }

        return $this->completeTenantRegistration($data, $databaseProvisioner);
    }

    public function createStripeRegistrationSession(Request $request): JsonResponse
    {
        $data = $this->validateTenantRegistrationData($request);

        if ($data['payment_method'] !== 'stripe') {
            return response()->json([
                'message' => 'Stripe payment method is required for modal checkout.',
            ], 422);
        }

        $monthlyPlanPrice = (float) config('plans.' . $data['plan'] . '.price', 0);
        $totalSubscriptionAmount = $monthlyPlanPrice * $data['subscription_months'];

        $stripeSecret = (string) config('services.stripe.secret');
        $stripePublishableKey = (string) config('services.stripe.key');

        if ($stripeSecret === '' || $stripePublishableKey === '') {
            return response()->json([
                'message' => 'Stripe is not configured. Set STRIPE_KEY and STRIPE_SECRET first.',
            ], 422);
        }

        $amountInCents = (int) round($totalSubscriptionAmount * 100);

        if ($amountInCents <= 0) {
            return response()->json([
                'message' => 'The selected plan has an invalid Stripe amount.',
            ], 422);
        }

        $registrationToken = (string) Str::uuid();
        $request->session()->put("tenant_registration.pending.{$registrationToken}", $data);

        try {
            Stripe::setApiKey($stripeSecret);

            $checkoutSession = StripeCheckoutSession::create([
                'mode' => 'payment',
                'ui_mode' => 'embedded',
                'customer_email' => $data['email'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'php',
                        'unit_amount' => $amountInCents,
                        'product_data' => [
                            'name' => sprintf('%s Plan Subscription', ucfirst($data['plan'])),
                            'description' => sprintf('%d month(s) for %s.%s', (int) $data['subscription_months'], $data['subdomain'], config('app.domain')),
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'registration_token' => $registrationToken,
                    'subdomain' => $data['subdomain'],
                    'plan' => $data['plan'],
                    'months' => (string) $data['subscription_months'],
                ],
                'return_url' => route('tenant.register.payment.success', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to create embedded Stripe checkout session for tenant registration.', [
                'error' => $exception->getMessage(),
                'subdomain' => $data['subdomain'],
                'email' => $data['email'],
            ]);

            $request->session()->forget("tenant_registration.pending.{$registrationToken}");

            return response()->json([
                'message' => 'Unable to start Stripe checkout. Please try again.',
            ], 500);
        }

        return response()->json([
            'clientSecret' => (string) $checkoutSession->client_secret,
            'publishableKey' => $stripePublishableKey,
        ]);
    }

    public function stripeSuccess(Request $request, TenantDatabaseProvisioner $databaseProvisioner): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            return redirect()->route('tenant.register')
                ->withErrors(['payment_method' => 'Missing Stripe session id.']);
        }

        $stripeSecret = (string) config('services.stripe.secret');

        if ($stripeSecret === '') {
            return redirect()->route('tenant.register')
                ->withErrors(['payment_method' => 'Stripe is not configured. Set STRIPE_SECRET first.']);
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $checkoutSession = StripeCheckoutSession::retrieve($sessionId);
        } catch (\Throwable $exception) {
            Log::warning('Stripe checkout session retrieval failed during tenant registration.', [
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('tenant.register')
                ->withErrors(['payment_method' => 'Unable to verify Stripe payment.']);
        }

        if (($checkoutSession->payment_status ?? null) !== 'paid') {
            return redirect()->route('tenant.register')
                ->withErrors(['payment_method' => 'Stripe payment is not marked as paid yet.']);
        }

        $registrationToken = (string) ($checkoutSession->metadata->registration_token ?? '');

        if ($registrationToken === '') {
            return redirect()->route('tenant.register')
                ->withErrors(['payment_method' => 'Missing Stripe registration token.']);
        }

        $pendingKey = "tenant_registration.pending.{$registrationToken}";
        $pendingData = $request->session()->get($pendingKey);

        if (!is_array($pendingData)) {
            return redirect()->route('tenant.register')
                ->withErrors(['payment_method' => 'Your registration session expired. Please submit registration again.']);
        }

        $pendingData['payment_method'] = 'stripe';
        $pendingData['stripe_checkout_session_id'] = $sessionId;
        $pendingData['stripe_payment_intent_id'] = (string) ($checkoutSession->payment_intent ?? '');

        $response = $this->completeTenantRegistration($pendingData, $databaseProvisioner);

        $request->session()->forget($pendingKey);

        return $response;
    }

    protected function startStripeRegistrationCheckout(Request $request, array $data, float $totalSubscriptionAmount): RedirectResponse
    {
        $stripeSecret = (string) config('services.stripe.secret');

        if ($stripeSecret === '') {
            return redirect()->back()
                ->withInput()
                ->withErrors(['payment_method' => 'Stripe is not configured. Set STRIPE_SECRET first.']);
        }

        $amountInCents = (int) round($totalSubscriptionAmount * 100);

        if ($amountInCents <= 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['payment_method' => 'The selected plan has an invalid Stripe amount.']);
        }

        $registrationToken = (string) Str::uuid();
        $request->session()->put("tenant_registration.pending.{$registrationToken}", $data);

        try {
            Stripe::setApiKey($stripeSecret);

            $checkoutSession = StripeCheckoutSession::create([
                'mode' => 'payment',
                'customer_email' => $data['email'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'php',
                        'unit_amount' => $amountInCents,
                        'product_data' => [
                            'name' => sprintf('%s Plan Subscription', ucfirst($data['plan'])),
                            'description' => sprintf('%d month(s) for %s.%s', (int) $data['subscription_months'], $data['subdomain'], config('app.domain')),
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'registration_token' => $registrationToken,
                    'subdomain' => $data['subdomain'],
                    'plan' => $data['plan'],
                    'months' => (string) $data['subscription_months'],
                ],
                'success_url' => route('tenant.register.payment.success', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('tenant.register', [], true) . '?payment=cancelled',
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to create Stripe checkout session for tenant registration.', [
                'error' => $exception->getMessage(),
                'subdomain' => $data['subdomain'],
                'email' => $data['email'],
            ]);

            $request->session()->forget("tenant_registration.pending.{$registrationToken}");

            return redirect()->back()
                ->withInput()
                ->withErrors(['payment_method' => 'Unable to start Stripe checkout. Please try again.']);
        }

        return redirect()->away((string) $checkoutSession->url);
    }

    protected function validateTenantRegistrationData(Request $request): array
    {
        $allowedPlans = implode(',', array_keys(config('plans')));

        $data = $request->validate([
            'shop_name' => 'required',
            'address' => 'required|string|max:255',
            'subdomain' => 'required|unique:tenants,subdomain|alpha_dash',
            'plan' => "required|in:{$allowedPlans}",
            'payment_method' => 'required|in:gcash,stripe',
            'subscription_months' => 'required|integer|min:1|max:24',
            'name' => 'required',
            'email' => 'required|email',
            'terms' => 'accepted',
        ]);

        $data['subdomain'] = strtolower(Str::slug((string) $data['subdomain']));
        $data['plan'] = strtolower(trim((string) $data['plan']));
        $data['payment_method'] = strtolower(trim((string) $data['payment_method']));
        $data['subscription_months'] = (int) $data['subscription_months'];

        return $data;
    }

    protected function completeTenantRegistration(array $data, TenantDatabaseProvisioner $databaseProvisioner): RedirectResponse
    {

        $leaseStart = Carbon::now();
        $leaseEnd = $leaseStart->copy()->addMonths($data['subscription_months']);

        $monthlyPlanPrice = (float) config('plans.' . $data['plan'] . '.price', 0);
        $totalSubscriptionAmount = $monthlyPlanPrice * (int) $data['subscription_months'];

        $databaseName = $databaseProvisioner->generateDatabaseName($data['subdomain']);
        $tenant = Tenant::create([
            'name' => $data['shop_name'],
            'subdomain' => $data['subdomain'],
            'plan' => $data['plan'],
            'lease_starts_at' => $leaseStart,
            'lease_ends_at' => $leaseEnd,
            'settings' => [
                'status' => [
                    'registration' => 'pending',
                    'requested_at' => now()->toIso8601String(),
                ],
                'address' => $data['address'],
                'subscription' => [
                    'payment_method' => $data['payment_method'],
                    'months' => $data['subscription_months'],
                    'monthly_price' => $monthlyPlanPrice,
                    'total_amount' => $totalSubscriptionAmount,
                    'currency' => 'PHP',
                    'payment_status' => $data['payment_method'] === 'stripe' ? 'paid' : 'pending',
                    'stripe_checkout_session_id' => $data['stripe_checkout_session_id'] ?? null,
                    'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? null,
                ],
                'database' => [
                    'host' => config('tenancy.tenant_host'),
                    'port' => config('tenancy.tenant_port'),
                    'database' => $databaseName,
                    'username' => config('tenancy.tenant_username'),
                    'password' => config('tenancy.tenant_password'),
                ],
                'onboarding' => [
                    'owner' => [
                        'name' => (string) $data['name'],
                        'email' => (string) $data['email'],
                    ],
                ],
            ],
        ]);

        try {
            Mail::to((string) $data['email'])->send(new TenantRegistrationReceivedMail($tenant));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send tenant registration received email.', [
                'tenant_id' => $tenant->id,
                'email' => (string) $data['email'],
                'error' => $exception->getMessage(),
            ]);
        }

        $payload = [
            'success' => 'Registration in progress. We’ll email your login link and credentials once approved.',
            'tenant_subdomain' => $tenant->subdomain,
        ];

        return redirect()->route('tenant.register')->with($payload);
    }

    protected function configureTenantConnection(Tenant $tenant): void
    {
        $database = data_get($tenant->settings, 'database');

        if (! is_array($database) || empty($database['database'])) {
            throw new \RuntimeException('Tenant database settings are missing.');
        }

        config([
            'database.connections.tenant.host' => $database['host'] ?? config('database.connections.tenant.host'),
            'database.connections.tenant.port' => $database['port'] ?? config('database.connections.tenant.port'),
            'database.connections.tenant.database' => $database['database'],
            'database.connections.tenant.username' => $database['username'] ?? config('database.connections.tenant.username'),
            'database.connections.tenant.password' => $database['password'] ?? config('database.connections.tenant.password'),
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
