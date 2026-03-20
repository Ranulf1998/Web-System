<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
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

        $tenant = Tenant::on('central')->where('subdomain', $subdomain)->firstOrFail();

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

            $tenantUser = null;

            if ($currentUser && isset($currentUser->email)) {
                $tenantUser = \App\Models\User::on('tenant')
                    ->where('email', (string) $currentUser->email)
                    ->first();
            }

            if (! $tenantUser && $sessionUserId !== null) {
                $tenantUser = \App\Models\User::on('tenant')->find($sessionUserId);
            }

            if (! $tenantUser || (int) $tenantUser->tenant_id !== (int) $tenant->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('tenant.login', ['subdomain' => $tenant->subdomain]);
            }

            Auth::setUser($tenantUser);
        }

        return $next($request);
    }
}