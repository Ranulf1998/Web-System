<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;


class Tenant extends Model
{
    protected $connection = 'central';

    protected $fillable = ['name', 'subdomain', 'plan', 'lease_starts_at', 'lease_ends_at', 'settings'];

    protected $casts = [
        'settings' => 'array',
        'lease_starts_at' => 'datetime',
        'lease_ends_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function planKey(): string
    {
        $plan = strtolower(trim((string) $this->plan));

        if (str_contains($plan, 'starter')) {
            return 'starter';
        }

        if (str_contains($plan, 'standard')) {
            return 'standard';
        }

        if (str_contains($plan, 'business')) {
            return 'business';
        }

        return $plan;
    }

    // Helper to check if tenant can use a feature based on plan
    public function canUseFeature($feature)
    {
        $planKey = $this->planKey();

        if ($planKey === 'business') {
            return true;
        }

        $plans = config('plans');
        return in_array($feature, $plans[$planKey]['features'] ?? [], true);
    }

    public function ensureRolesAndPermissions(): void
    {
        $tenantId = (int) $this->getKey();

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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $ownerRole = Role::firstOrCreate([
            'name' => 'Owner',
            'guard_name' => 'web',
            'tenant_id' => $tenantId,
        ]);
        $ownerRole->syncPermissions($permissions);

        $cashierRole = Role::firstOrCreate([
            'name' => 'Cashier',
            'guard_name' => 'web',
            'tenant_id' => $tenantId,
        ]);
        $cashierRole->syncPermissions([
            'use pos',
            'create orders',
            'process payments',
            'view products',
            'view brewing guides',
        ]);

        $baristaRole = Role::firstOrCreate([
            'name' => 'Barista',
            'guard_name' => 'web',
            'tenant_id' => $tenantId,
        ]);
        $baristaRole->syncPermissions([
            'manage brewing orders',
            'view brewing guides',
        ]);
    }
}
