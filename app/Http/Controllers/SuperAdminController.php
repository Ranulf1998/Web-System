<?php

namespace App\Http\Controllers;

use App\Mail\TenantApprovedMail;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Services\RecaptchaVerifier;
use App\Services\TenantDatabaseProvisioner;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Tenancy;

class SuperAdminController extends Controller
{
    protected function centralRoleOptions(): array
    {
        return [
            'Platform Owner',
            'Platform Admin',
            'Support Admin',
        ];
    }

    protected function ensureCentralRole(string $roleName): Role
    {
        return Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'tenant_id' => null,
        ]);
    }

    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if ($request->user()?->tenant_id === null) {
                return redirect()->route('super-admin.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('super-admin.login')
                ->with('status', 'Tenant session ended. Please sign in as super admin.');
        }

        return view('super-admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'g-recaptcha-response' => ['required', 'string'],
        ]);

        app(RecaptchaVerifier::class)->ensureValid(
            $request->input('g-recaptcha-response'),
            $request->ip()
        );

        $credentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()?->tenant_id !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This login is only for BrewCloud super-admin accounts.',
            ]);
        }

        return redirect()->intended(route('super-admin.dashboard'));
    }

    public function dashboard(): View
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $safeTableCount = static function (string $table): int {
            if (! Schema::connection('central')->hasTable($table)) {
                return 0;
            }

            return (int) DB::connection('central')->table($table)->count();
        };

        $safeTenantMetricCount = static function (string $metric, callable $resolver): int {
            try {
                return (int) $resolver();
            } catch (\Throwable $exception) {
                Log::warning('Unable to resolve tenant metric for super admin dashboard.', [
                    'metric' => $metric,
                    'error' => $exception->getMessage(),
                ]);

                return 0;
            }
        };

        $currentTenants = Tenant::query()
            ->latest()
            ->get(['id', 'name', 'subdomain', 'plan', 'lease_starts_at', 'lease_ends_at', 'settings', 'created_at']);

        $databaseNames = $currentTenants
            ->map(fn (Tenant $tenant) => data_get($tenant->settings, 'database.database'))
            ->filter(fn ($databaseName) => is_string($databaseName) && $databaseName !== '')
            ->unique()
            ->values();

        $databaseUsageByName = [];

        if ($databaseNames->isNotEmpty()) {
            try {
                $databaseUsageByName = DB::connection('central')
                    ->table('information_schema.TABLES')
                    ->selectRaw('TABLE_SCHEMA as database_name, COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) as total_bytes')
                    ->whereIn('TABLE_SCHEMA', $databaseNames->all())
                    ->groupBy('TABLE_SCHEMA')
                    ->pluck('total_bytes', 'database_name')
                    ->map(fn ($bytes) => (int) $bytes)
                    ->toArray();
            } catch (\Throwable) {
                $databaseUsageByName = [];
            }
        }

        $currentTenants = $currentTenants->map(function (Tenant $tenant) use ($databaseUsageByName) {
            $databaseName = data_get($tenant->settings, 'database.database');
            $databaseBytes = is_string($databaseName)
                ? ($databaseUsageByName[$databaseName] ?? null)
                : null;
            $registrationStatus = strtolower(trim((string) data_get($tenant->settings, 'status.registration', 'approved')));
            $subscribedMonths = (int) data_get($tenant->settings, 'subscription.months', 0);
            $leaseStart = $tenant->lease_starts_at ?? $tenant->created_at;
            $leaseEnd = $tenant->lease_ends_at ?? $leaseStart?->copy()->addMonths(max($subscribedMonths, 1));
            $suspendedAtRaw = data_get($tenant->settings, 'status.suspended_at');
            $isSuspended = is_string($suspendedAtRaw) && trim($suspendedAtRaw) !== '';

            $displaySubscribedMonths = $subscribedMonths;

            if ($displaySubscribedMonths < 1 && $leaseStart && $leaseEnd) {
                $displaySubscribedMonths = (int) ceil($leaseStart->diffInDays($leaseEnd) / 30);
            }

            $displaySubscribedMonths = max($displaySubscribedMonths, 1);
            $ownerSummary = $this->fetchTenantOwnerSummary($tenant);
            $pendingOwnerName = trim((string) data_get($tenant->settings, 'onboarding.owner.name', ''));
            $pendingOwnerEmail = trim((string) data_get($tenant->settings, 'onboarding.owner.email', ''));

            $displayOwnerName = $ownerSummary['name'] ?? 'N/A';
            if ($displayOwnerName === 'N/A' && $pendingOwnerName !== '') {
                $displayOwnerName = $pendingOwnerName;
            }

            $displayOwnerEmail = $ownerSummary['email'] ?? 'N/A';
            if ($displayOwnerEmail === 'N/A' && $pendingOwnerEmail !== '') {
                $displayOwnerEmail = $pendingOwnerEmail;
            }

            $displayAddress = null;
            $addressPaths = [
                'contact.address',
                'shop.address',
                'business.address',
                'tenant.address',
                'address',
            ];

            foreach ($addressPaths as $addressPath) {
                $candidate = data_get($tenant->settings, $addressPath);

                if (is_string($candidate) && trim($candidate) !== '') {
                    $displayAddress = trim($candidate);
                    break;
                }
            }

            $tenant->setAttribute('database_name', $databaseName);
            $tenant->setAttribute('database_bytes', $databaseBytes);
            $tenant->setAttribute('display_lease_starts_at', $leaseStart);
            $tenant->setAttribute('display_lease_ends_at', $leaseEnd);
            $tenant->setAttribute('display_subscription_months', $displaySubscribedMonths);
            $tenant->setAttribute('display_tenant_email', $displayOwnerEmail);
            $tenant->setAttribute('display_tenant_address', $displayAddress ?: 'N/A');
            $tenant->setAttribute('display_owner_name', $displayOwnerName);
            $tenant->setAttribute('display_users_count', (int) ($ownerSummary['users_count'] ?? 0));
            $tenant->setAttribute('display_payment_method', (string) data_get($tenant->settings, 'subscription.payment_method', 'N/A'));
            $tenant->setAttribute('display_monthly_price', (float) data_get($tenant->settings, 'subscription.monthly_price', (float) config('plans.' . $tenant->planKey() . '.price', 0)));
            $tenant->setAttribute('display_renewal_history', array_values(array_filter(
                is_array(data_get($tenant->settings, 'subscription.renewal_history', []))
                    ? data_get($tenant->settings, 'subscription.renewal_history', [])
                    : [],
                fn ($entry) => is_array($entry)
            )));
            $tenant->setAttribute('display_is_suspended', $isSuspended);
            $tenant->setAttribute('display_suspended_at', $isSuspended ? Carbon::parse($suspendedAtRaw) : null);
            $tenant->setAttribute('display_suspension_reason', (string) data_get($tenant->settings, 'status.suspension_reason', ''));
            $tenant->setAttribute('display_registration_status', $registrationStatus);
            $tenant->setAttribute('display_requested_at', data_get($tenant->settings, 'status.requested_at'));
            $tenant->setAttribute('display_approved_at', data_get($tenant->settings, 'status.approved_at'));
            $tenant->setAttribute('display_declined_at', data_get($tenant->settings, 'status.declined_at'));
            $tenant->setAttribute('display_decline_reason', (string) data_get($tenant->settings, 'status.decline_reason', ''));

            return $tenant;
        });

        $activeSubscriptions = $currentTenants->filter(function (Tenant $tenant) {
            $leaseEnd = $tenant->display_lease_ends_at;

            if (($tenant->display_registration_status ?? 'approved') !== 'approved') {
                return false;
            }

            return ! $tenant->display_is_suspended
                && $leaseEnd
                && $leaseEnd->copy()->endOfDay()->greaterThanOrEqualTo(now());
        });

        $expiringSoon = $activeSubscriptions->filter(function (Tenant $tenant) {
            return $tenant->display_lease_ends_at->lessThanOrEqualTo(now()->copy()->addDays(7));
        });

        $inactiveSubscriptionsCount = max((int) $currentTenants->count() - (int) $activeSubscriptions->count(), 0);

        $estimatedMrr = $activeSubscriptions->sum(function (Tenant $tenant) {
            return (float) ($tenant->display_monthly_price ?? config('plans.' . $tenant->planKey() . '.price', 0));
        });

        $mrrByPlan = $activeSubscriptions
            ->groupBy(fn (Tenant $tenant) => $tenant->planKey())
            ->map(function ($tenants, $planKey) {
                $monthlyMrr = (float) $tenants->sum(fn (Tenant $tenant) => $tenant->display_monthly_price ?? config('plans.' . $tenant->planKey() . '.price', 0));

                return [
                    'plan' => ucfirst((string) $planKey),
                    'tenants' => (int) $tenants->count(),
                    'mrr' => $monthlyMrr,
                ];
            })
            ->sortByDesc('mrr')
            ->values();

        $expiringSubscriptionRows = $activeSubscriptions
            ->map(function (Tenant $tenant) {
                $leaseEnd = $tenant->display_lease_ends_at;
                $daysLeft = $leaseEnd ? now()->startOfDay()->diffInDays($leaseEnd->copy()->startOfDay(), false) : null;

                return [
                    'tenant_name' => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                    'plan' => ucfirst((string) $tenant->plan),
                    'lease_end' => $leaseEnd,
                    'days_left' => $daysLeft,
                ];
            })
            ->sortBy('days_left')
            ->take(10)
            ->values();

        $recentSubscriptionPayments = $currentTenants
            ->flatMap(function (Tenant $tenant) {
                $history = $tenant->display_renewal_history;

                if (! is_array($history)) {
                    return [];
                }

                return collect($history)
                    ->filter(fn ($entry) => is_array($entry))
                    ->map(function (array $entry) use ($tenant) {
                        $paidAtRaw = (string) ($entry['paid_at'] ?? '');
                        $paidAt = null;

                        if ($paidAtRaw !== '') {
                            try {
                                $paidAt = Carbon::parse($paidAtRaw);
                            } catch (\Throwable) {
                                $paidAt = null;
                            }
                        }

                        return [
                            'tenant_name' => $tenant->name,
                            'subdomain' => $tenant->subdomain,
                            'paid_at' => $paidAt,
                            'payment_method' => strtoupper((string) ($entry['payment_method'] ?? 'N/A')),
                            'amount' => (float) ($entry['amount'] ?? 0),
                        ];
                    });
            })
            ->sortByDesc(fn (array $row) => $row['paid_at']?->getTimestamp() ?? 0)
            ->take(10)
            ->values();

        $totalDatabaseBytes = (int) $currentTenants->sum(function (Tenant $tenant) {
            return is_int($tenant->database_bytes) ? $tenant->database_bytes : 0;
        });

        $subscriptionSalesTotal = (float) $currentTenants->sum(function (Tenant $tenant) {
            return (float) data_get($tenant->settings, 'subscription.total_amount', 0);
        });

        $tenantUsersTotal = (int) $currentTenants->sum(function (Tenant $tenant) {
            return (int) ($tenant->display_users_count ?? 0);
        });

        $stats = [
            'tenants' => (int) $currentTenants->count(),
            'pending_registrations' => (int) $currentTenants->filter(function (Tenant $tenant) {
                return strtolower((string) ($tenant->display_registration_status ?? 'approved')) === 'pending';
            })->count(),
            'active_subscriptions' => (int) $activeSubscriptions->count(),
            'inactive_subscriptions' => $inactiveSubscriptionsCount,
            'expiring_soon' => (int) $expiringSoon->count(),
            'estimated_mrr' => $estimatedMrr,
            'tenant_users' => $tenantUsersTotal,
            'super_admins' => User::whereNull('tenant_id')->count(),
            'orders' => $safeTenantMetricCount('orders', static fn (): int => Order::count()),
            'products' => $safeTenantMetricCount('products', static fn (): int => Product::count()),
            'sales_total' => $subscriptionSalesTotal,
            'total_database_bytes' => $totalDatabaseBytes,
            'failed_jobs' => $safeTableCount('failed_jobs'),
            'pending_jobs' => $safeTableCount('jobs'),
            'activity_logs' => $safeTableCount('activity_logs'),
            'support_tickets' => $safeTableCount('support_tickets'),
        ];

        $centralAdmins = User::query()
            ->whereNull('tenant_id')
            ->with('roles')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'created_at']);

        $supportTickets = SupportTicket::query()
            ->latest()
            ->limit(50)
            ->get([
                'id',
                'tenant_id',
                'shop_name',
                'subdomain',
                'subject',
                'message',
                'status',
                'created_at',
                'resolved_at',
                'resolution_note',
            ]);

        return view('super-admin.dashboard', [
            'stats' => $stats,
            'currentTenants' => $currentTenants,
            'mrrByPlan' => $mrrByPlan,
            'expiringSubscriptionRows' => $expiringSubscriptionRows,
            'recentSubscriptionPayments' => $recentSubscriptionPayments,
            'centralAdmins' => $centralAdmins,
            'centralRoleOptions' => $this->centralRoleOptions(),
            'supportTickets' => $supportTickets,
        ]);
    }

    public function storeCentralAdmin(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $roleOptions = $this->centralRoleOptions();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'role' => ['required', 'string', 'in:' . implode(',', $roleOptions)],
        ]);

        $user = User::create([
            'tenant_id' => null,
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
        ]);

        $role = $this->ensureCentralRole($validated['role']);
        $user->syncRoles([$role]);

        return redirect()->route('super-admin.dashboard')->with('status', 'Central admin account created.');
    }

    public function updateCentralAdminRole(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);
        abort_unless($user->tenant_id === null, 404);

        $roleOptions = $this->centralRoleOptions();
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', $roleOptions)],
        ]);

        $role = $this->ensureCentralRole($validated['role']);
        $user->syncRoles([$role]);

        return redirect()->route('super-admin.dashboard')->with('status', 'Central admin role updated.');
    }

    public function destroyCentralAdmin(User $user): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);
        abort_unless($user->tenant_id === null, 404);

        if ((int) auth()->id() === (int) $user->id) {
            return redirect()->route('super-admin.dashboard')->withErrors([
                'central_admin' => 'You cannot remove your own account.',
            ]);
        }

        $user->syncRoles([]);
        $user->delete();

        return redirect()->route('super-admin.dashboard')->with('status', 'Central admin removed.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }

    public function suspendTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $tenant->settings ?? [];

        data_set($settings, 'status.suspended_at', now()->toIso8601String());
        data_set($settings, 'status.suspended_by', auth()->id());

        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason !== '') {
            data_set($settings, 'status.suspension_reason', $reason);
        }

        $tenant->update([
            'settings' => $settings,
        ]);

        return redirect()->route('super-admin.dashboard')->with('status', "Tenant '{$tenant->name}' suspended.");
    }

    public function unsuspendTenant(Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $settings = $tenant->settings ?? [];

        data_forget($settings, 'status.suspended_at');
        data_forget($settings, 'status.suspended_by');
        data_forget($settings, 'status.suspension_reason');

        $tenant->update([
            'settings' => $settings,
        ]);

        return redirect()->route('super-admin.dashboard')->with('status', "Tenant '{$tenant->name}' unsuspended.");
    }

    public function approveTenant(Tenant $tenant, TenantDatabaseProvisioner $databaseProvisioner): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $settings = $tenant->settings ?? [];

        $registrationStatus = strtolower(trim((string) data_get($settings, 'status.registration', 'approved')));
        if ($registrationStatus !== 'pending' && $registrationStatus !== 'declined') {
            return redirect()->route('super-admin.dashboard')->with('status', "Tenant '{$tenant->name}' is already approved.");
        }

        $ownerName = trim((string) data_get($settings, 'onboarding.owner.name', ''));
        $ownerEmail = trim((string) data_get($settings, 'onboarding.owner.email', ''));

        if ($ownerName === '' || $ownerEmail === '') {
            return redirect()->route('super-admin.dashboard')->withErrors([
                'tenant_approval' => "Tenant '{$tenant->name}' is missing onboarding owner details.",
            ]);
        }

        $generatedPassword = $this->generateStrongPassword(12);
        $ownerPasswordHash = Hash::make($generatedPassword);

        $databaseName = (string) data_get($settings, 'database.database', '');
        if ($databaseName === '') {
            $databaseName = $databaseProvisioner->generateDatabaseName($tenant->subdomain);
            data_set($settings, 'database.host', config('tenancy.tenant_host'));
            data_set($settings, 'database.port', config('tenancy.tenant_port'));
            data_set($settings, 'database.database', $databaseName);
            data_set($settings, 'database.username', config('tenancy.tenant_username'));
            data_set($settings, 'database.password', config('tenancy.tenant_password'));
        }

        $databaseCreated = false;
        $databaseExisted = (bool) DB::connection('central')
            ->table('information_schema.SCHEMATA')
            ->where('SCHEMA_NAME', $databaseName)
            ->exists();

        try {
            $databaseProvisioner->createDatabase($databaseName);
            $databaseCreated = true;

            $tenant->update([
                'settings' => $settings,
            ]);

            $databaseProvisioner->runTenantMigrations($tenant);

            $this->configureTenantConnection($tenant);
            $tenancy = app(Tenancy::class);
            $tenancy->initialize($tenant);

            try {
                DB::connection('tenant')->transaction(function () use ($tenant, $ownerName, $ownerEmail, $ownerPasswordHash) {
                    $user = User::on('tenant')->where('email', $ownerEmail)->first();

                    if (! $user) {
                        $user = User::on('tenant')->create([
                            'tenant_id' => $tenant->id,
                            'name' => $ownerName,
                            'email' => $ownerEmail,
                            'password' => $ownerPasswordHash,
                        ]);
                    } else {
                        $user->fill([
                            'tenant_id' => $tenant->id,
                            'name' => $ownerName,
                            'password' => $ownerPasswordHash,
                        ])->save();
                    }

                    $ownerRole = $this->seedTenantRoles($tenant);
                    $user->assignRole($ownerRole);

                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                });
            } finally {
                $tenancy->end();
            }
        } catch (\Throwable $exception) {
            if ($databaseCreated && ! $databaseExisted) {
                $databaseProvisioner->dropDatabase($databaseName);
            }

            return redirect()->route('super-admin.dashboard')->withErrors([
                'tenant_approval' => "Failed to approve tenant '{$tenant->name}': " . $exception->getMessage(),
            ]);
        }

        data_set($settings, 'status.registration', 'approved');
        data_set($settings, 'status.approved_at', now()->toIso8601String());
        data_set($settings, 'status.approved_by', auth()->id());
        data_forget($settings, 'status.declined_at');
        data_forget($settings, 'status.declined_by');
        data_forget($settings, 'status.decline_reason');

        $tenant->update([
            'settings' => $settings,
        ]);

        try {
            $tenantLoginUrl = route('tenant.login', ['subdomain' => $tenant->subdomain]);
            Mail::to($ownerEmail)->send(new TenantApprovedMail($tenant, $ownerEmail, $tenantLoginUrl, $generatedPassword));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send tenant approval email.', [
                'tenant_id' => $tenant->id,
                'email' => $ownerEmail,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('super-admin.dashboard')->with('status', "Tenant '{$tenant->name}' approved.");
    }

    public function declineTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $tenant->settings ?? [];

        data_set($settings, 'status.registration', 'declined');
        data_set($settings, 'status.declined_at', now()->toIso8601String());
        data_set($settings, 'status.declined_by', auth()->id());
        data_forget($settings, 'status.approved_at');
        data_forget($settings, 'status.approved_by');

        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason !== '') {
            data_set($settings, 'status.decline_reason', $reason);
        } else {
            data_forget($settings, 'status.decline_reason');
        }

        $tenant->update([
            'settings' => $settings,
        ]);

        return redirect()->route('super-admin.dashboard')->with('status', "Tenant '{$tenant->name}' declined.");
    }

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

    protected function generateStrongPassword(int $length = 12): string
    {
        $length = max($length, 8);

        $upperChars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowerChars = 'abcdefghijkmnopqrstuvwxyz';
        $numberChars = '23456789';
        $symbolChars = '!@#$%^&*()-_=+[]{}?';

        $required = [
            $upperChars[random_int(0, strlen($upperChars) - 1)],
            $lowerChars[random_int(0, strlen($lowerChars) - 1)],
            $numberChars[random_int(0, strlen($numberChars) - 1)],
            $symbolChars[random_int(0, strlen($symbolChars) - 1)],
        ];

        $allChars = $upperChars . $lowerChars . $numberChars . $symbolChars;
        $remainingCount = $length - count($required);

        for ($index = 0; $index < $remainingCount; $index++) {
            $required[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        shuffle($required);
        $password = implode('', $required);

        $policyPassed =
            preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);

        if (! $policyPassed) {
            return $this->generateStrongPassword($length);
        }

        return $password;
    }

    public function renewTenantSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $validated = $request->validate([
            'add_months' => ['required', 'integer', 'min:1', 'max:24'],
            'payment_method' => ['required', 'in:gcash,bank'],
        ]);

        $addMonths = (int) $validated['add_months'];
        $paymentMethod = strtolower(trim((string) $validated['payment_method']));
        $monthlyPrice = (float) config('plans.' . $tenant->planKey() . '.price', 0);

        $leaseStart = $tenant->lease_starts_at ?? $tenant->created_at ?? now();
        $currentLeaseEnd = $tenant->lease_ends_at;
        $effectiveBase = $currentLeaseEnd && $currentLeaseEnd->isFuture() ? $currentLeaseEnd->copy() : now();
        $newLeaseEnd = $effectiveBase->addMonths($addMonths);

        $settings = $tenant->settings ?? [];
        $currentMonths = (int) data_get($settings, 'subscription.months', 0);
        $newMonths = max($currentMonths + $addMonths, $addMonths);

        data_set($settings, 'subscription.months', $newMonths);
        data_set($settings, 'subscription.payment_method', $paymentMethod);
        data_set($settings, 'subscription.monthly_price', $monthlyPrice);
        data_set($settings, 'subscription.total_amount', $monthlyPrice * $newMonths);
        data_set($settings, 'subscription.currency', 'PHP');
        data_set($settings, 'subscription.last_added_months', $addMonths);
        data_set($settings, 'subscription.last_payment_method', $paymentMethod);
        data_set($settings, 'subscription.last_payment_amount', $monthlyPrice * $addMonths);
        data_set($settings, 'subscription.last_paid_at', now()->toIso8601String());

        $history = data_get($settings, 'subscription.renewal_history', []);
        if (! is_array($history)) {
            $history = [];
        }

        array_unshift($history, [
            'paid_at' => now()->toIso8601String(),
            'payment_method' => $paymentMethod,
            'amount' => $monthlyPrice * $addMonths,
            'months_added' => $addMonths,
        ]);

        $history = array_slice($history, 0, 10);
        data_set($settings, 'subscription.renewal_history', $history);

        $tenant->update([
            'lease_starts_at' => $leaseStart,
            'lease_ends_at' => $newLeaseEnd,
            'settings' => $settings,
        ]);

        return redirect()->route('super-admin.dashboard')->with('status', "Subscription renewed for '{$tenant->name}'.");
    }

    public function changeTenantSubscriptionPlan(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $planKeys = array_keys(config('plans', []));

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:' . implode(',', $planKeys)],
        ]);

        $newPlan = strtolower(trim((string) $validated['plan']));
        $monthlyPrice = (float) config('plans.' . $newPlan . '.price', 0);

        $settings = $tenant->settings ?? [];
        $currentMonths = (int) data_get($settings, 'subscription.months', 0);
        $currentMonths = max($currentMonths, 1);

        data_set($settings, 'subscription.monthly_price', $monthlyPrice);
        data_set($settings, 'subscription.total_amount', $monthlyPrice * $currentMonths);
        data_set($settings, 'subscription.currency', 'PHP');
        data_set($settings, 'subscription.last_plan_change_at', now()->toIso8601String());
        data_set($settings, 'subscription.last_plan_changed_by', auth()->id());

        $tenant->update([
            'plan' => $newPlan,
            'settings' => $settings,
        ]);

        return redirect()->route('super-admin.dashboard')->with('status', "Subscription plan updated for '{$tenant->name}'.");
    }

    public function updateSupportTicketStatus(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id === null, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $validated['status'];
        $resolutionNote = trim((string) ($validated['resolution_note'] ?? ''));

        $supportTicket->status = $status;
        $supportTicket->resolution_note = $resolutionNote !== '' ? $resolutionNote : null;
        $supportTicket->resolved_at = $status === 'resolved' ? ($supportTicket->resolved_at ?? now()) : null;
        $supportTicket->save();

        return redirect()->route('super-admin.dashboard')->with('status', 'Support ticket updated.');
    }

    protected function configureTenantConnection(Tenant $tenant): bool
    {
        $database = data_get($tenant->settings, 'database');

        if (! is_array($database) || empty($database['database'])) {
            return false;
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

        return true;
    }

    protected function fetchTenantOwnerSummary(Tenant $tenant): array
    {
        if (! $this->configureTenantConnection($tenant)) {
            return [
                'name' => 'N/A',
                'email' => 'N/A',
                'users_count' => 0,
            ];
        }

        try {
            $usersQuery = DB::connection('tenant')->table('users');
            if (Schema::connection('tenant')->hasColumn('users', 'tenant_id')) {
                $usersQuery->where('tenant_id', (int) $tenant->id);
            }

            $usersCount = (int) $usersQuery->count();

            $ownerQuery = DB::connection('tenant')
                ->table('users')
                ->join('model_has_roles', function ($join) {
                    $join->on('model_has_roles.model_id', '=', 'users.id')
                        ->where('model_has_roles.model_type', '=', User::class);
                })
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'Owner')
                ->select('users.name', 'users.email')
                ->orderBy('users.id');

            if (Schema::connection('tenant')->hasColumn('users', 'tenant_id')) {
                $ownerQuery->where('users.tenant_id', (int) $tenant->id);
            }

            $owner = $ownerQuery->first();

            if (! $owner) {
                $fallbackQuery = DB::connection('tenant')
                    ->table('users')
                    ->select('name', 'email')
                    ->orderBy('id');

                if (Schema::connection('tenant')->hasColumn('users', 'tenant_id')) {
                    $fallbackQuery->where('tenant_id', (int) $tenant->id);
                }

                $owner = $fallbackQuery->first();
            }

            return [
                'name' => is_string($owner?->name ?? null) && trim((string) $owner->name) !== '' ? (string) $owner->name : 'N/A',
                'email' => is_string($owner?->email ?? null) && trim((string) $owner->email) !== '' ? (string) $owner->email : 'N/A',
                'users_count' => $usersCount,
            ];
        } catch (\Throwable) {
            return [
                'name' => 'N/A',
                'email' => 'N/A',
                'users_count' => 0,
            ];
        }
    }
}
