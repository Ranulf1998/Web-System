<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Tenancy;

class IdentifyTenant
{
    public function handle($request, Closure $next)
    {
        // Extract subdomain (e.g., 'shop' from 'shop.brewcloud.test')
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $mainDomain = config('app.domain');

        // Skip for main domain (e.g., 'brewcloud.test' without subdomain)
        if ($host === $mainDomain || $subdomain === 'www') {
            URL::defaults([]);
            return $next($request);
        }

        $tenantCacheSeconds = max((int) env('TENANT_RESOLVE_CACHE_SECONDS', 15), 1);
        $tenantCacheKey = 'tenant:subdomain:' . strtolower((string) $subdomain);
        $tenant = Cache::remember($tenantCacheKey, now()->addSeconds($tenantCacheSeconds), function () use ($subdomain) {
            return Tenant::on('central')->where('subdomain', $subdomain)->firstOrFail();
        });

        $registrationStatus = strtolower(trim((string) data_get($tenant->settings, 'status.registration', 'approved')));

        if ($registrationStatus === 'pending') {
            return response()->view('tenant.registration-status', [
                'tenant' => $tenant,
                'status' => 'pending',
                'requested_at' => (string) data_get($tenant->settings, 'status.requested_at', ''),
                'reason' => '',
            ], 423);
        }

        if ($registrationStatus === 'declined') {
            return response()->view('tenant.registration-status', [
                'tenant' => $tenant,
                'status' => 'declined',
                'requested_at' => (string) data_get($tenant->settings, 'status.requested_at', ''),
                'reason' => (string) data_get($tenant->settings, 'status.decline_reason', ''),
                'reviewed_at' => (string) data_get($tenant->settings, 'status.declined_at', ''),
            ], 403);
        }

        $suspendedAt = data_get($tenant->settings, 'status.suspended_at');
        $isSuspended = is_string($suspendedAt) && trim($suspendedAt) !== '';

        if ($isSuspended) {
            return response()->view('tenant.suspended', [
                'tenant' => $tenant,
                'suspended_at' => $suspendedAt,
                'reason' => (string) data_get($tenant->settings, 'status.suspension_reason', ''),
            ], 423);
        }

        app(Tenancy::class)->initialize($tenant);

        // Bind tenant to the service container for easy access
        app()->instance('tenant', $tenant);

        // Also share with views
        view()->share('tenant', $tenant);

        // Make route() automatically include {subdomain} for tenant routes.
        URL::defaults(['subdomain' => $tenant->subdomain]);

        // Reconcile authenticated session against the tenant database.
        // This prevents central-session users from leaking into tenant subdomains
        // while preserving valid tenant logins (including remember-me restoration).
        if (Auth::check()) {
            $currentUser = Auth::user();
            $sessionUserId = Auth::id();

            $authFingerprint = implode('|', [
                (string) $tenant->id,
                (string) ($sessionUserId ?? ''),
                strtolower(trim((string) ($currentUser?->email ?? ''))),
            ]);

            $validatedFingerprint = (string) $request->session()->get('tenant_auth.validated_fingerprint', '');
            $validatedAt = (int) $request->session()->get('tenant_auth.validated_at', 0);
            $authRevalidationSeconds = max((int) env('TENANT_AUTH_REVALIDATION_SECONDS', 60), 1);

            if (
                $validatedFingerprint === $authFingerprint
                && $validatedAt > 0
                && (time() - $validatedAt) <= $authRevalidationSeconds
            ) {
                return $next($request);
            }

            $tenantUser = null;

            $email = strtolower(trim((string) ($currentUser?->email ?? '')));
            $hasEmail = $email !== '';

            if ($hasEmail || $sessionUserId !== null) {
                $tenantUserQuery = User::on('tenant')
                    ->newQuery()
                    ->where(function ($query) use ($email, $sessionUserId) {
                        if ($email !== '') {
                            $query->orWhere('email', $email);
                        }

                        if ($sessionUserId !== null) {
                            $query->orWhere('id', (int) $sessionUserId);
                        }
                    });

                if ($hasEmail) {
                    $tenantUserQuery->orderByRaw('CASE WHEN email = ? THEN 0 ELSE 1 END', [$email]);
                }

                $tenantUser = $tenantUserQuery->first();
            }

            if (!$tenantUser || (int) $tenantUser->tenant_id !== (int) $tenant->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('tenant.login', ['subdomain' => $tenant->subdomain]);
            }

            Auth::setUser($tenantUser);
            $request->session()->put('tenant_auth.validated_fingerprint', $authFingerprint);
            $request->session()->put('tenant_auth.validated_at', time());
        }

        return $next($request);
    }
}