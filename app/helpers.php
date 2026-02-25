<?php

if (!function_exists('tenant')) {
    /**
     * Get current tenant from container.
     */
    function tenant(): ?\App\Models\Tenant
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        return $tenant instanceof \App\Models\Tenant ? $tenant : null;
    }
}