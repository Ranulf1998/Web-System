<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
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

        $tenant = Tenant::where('subdomain', $subdomain)->firstOrFail();

        $tenant->ensureRolesAndPermissions();

        // Bind tenant to the service container for easy access
        app()->instance('tenant', $tenant);

        // Also share with views
        view()->share('tenant', $tenant);

        // Make route() automatically include {subdomain} for tenant routes.
        URL::defaults(['subdomain' => $tenant->subdomain]);

        return $next($request);
    }
}