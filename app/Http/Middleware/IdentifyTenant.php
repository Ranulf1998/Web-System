<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

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

        $suspendedAt = data_get($tenant->settings, 'status.suspended_at');
        $isSuspended = is_string($suspendedAt) && trim($suspendedAt) !== '';

        if ($isSuspended) {
            return response()->view('tenant.suspended', [
                'tenant' => $tenant,
                'suspended_at' => $suspendedAt,
                'reason' => (string) data_get($tenant->settings, 'status.suspension_reason', ''),
            ], 423);
        }

        $this->configureTenantConnection($tenant);

        // Bind tenant to the service container for easy access
        app()->instance('tenant', $tenant);

        // Also share with views
        view()->share('tenant', $tenant);

        // Make route() automatically include {subdomain} for tenant routes.
        URL::defaults(['subdomain' => $tenant->subdomain]);

        return $next($request);
    }

    protected function configureTenantConnection(Tenant $tenant): void
    {
        $database = data_get($tenant->settings, 'database');

        if (!is_array($database) || empty($database['database'])) {
            abort(500, 'Tenant database is not configured.');
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