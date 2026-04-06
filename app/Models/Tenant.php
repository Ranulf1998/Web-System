<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\InvalidatesResolverCache;
use Stancl\Tenancy\Database\Concerns\TenantRun;


class Tenant extends Model implements TenantWithDatabase
{
    use CentralConnection, HasDatabase, TenantRun, InvalidatesResolverCache;

    protected $connection = 'central';

    protected $fillable = ['name', 'subdomain', 'plan', 'lease_starts_at', 'lease_ends_at', 'settings'];

    protected $casts = [
        'settings' => 'array',
        'lease_starts_at' => 'datetime',
        'lease_ends_at' => 'datetime',
    ];

    public static function internalPrefix(): string
    {
        return 'tenancy_';
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public function getInternal(string $key)
    {
        $database = data_get($this->settings, 'database', []);

        return match ($key) {
            'db_driver' => data_get($database, 'driver', config('database.connections.tenant.driver', 'mysql')),
            'db_name' => data_get($database, 'database'),
            'db_host' => data_get($database, 'host'),
            'db_port' => data_get($database, 'port'),
            'db_username' => data_get($database, 'username'),
            'db_password' => data_get($database, 'password'),
            'db_connection' => data_get($database, 'connection', $this->getAttribute(static::internalPrefix() . 'db_connection')),
            default => $this->getAttribute(static::internalPrefix() . $key),
        };
    }

    public function setInternal(string $key, $value)
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $database = is_array(data_get($settings, 'database')) ? data_get($settings, 'database') : [];

        if (str_starts_with($key, 'db_')) {
            $map = [
                'db_connection' => 'connection',
                'db_driver' => 'driver',
                'db_name' => 'database',
                'db_host' => 'host',
                'db_port' => 'port',
                'db_username' => 'username',
                'db_password' => 'password',
            ];

            if (array_key_exists($key, $map)) {
                $database[$map[$key]] = $value;
                $settings['database'] = $database;
                $this->settings = $settings;

                return $this;
            }
        }

        $this->setAttribute(static::internalPrefix() . $key, $value);

        return $this;
    }

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

    public function bandwidthLimitBytes(): ?int
    {
        $limit = data_get(config('plans.' . $this->planKey(), []), 'bandwidth_limit_bytes');

        if ($limit === null) {
            return null;
        }

        if (! is_numeric($limit)) {
            return null;
        }

        return max((int) $limit, 0);
    }

    public function currentMonthBandwidthUsageBytes(?string $monthKey = null): int
    {
        $resolvedMonthKey = $monthKey ?: now()->format('Y-m');
        $monthly = data_get($this->settings, 'usage.bandwidth_monthly', []);

        if (! is_array($monthly)) {
            return 0;
        }

        $bytes = $monthly[$resolvedMonthKey] ?? 0;

        return is_numeric($bytes) ? max((int) $bytes, 0) : 0;
    }

    public function isBandwidthLimitExceeded(?string $monthKey = null): bool
    {
        $limit = $this->bandwidthLimitBytes();

        if ($limit === null) {
            return false;
        }

        return $this->currentMonthBandwidthUsageBytes($monthKey) > $limit;
    }

    public function remainingBandwidthBytes(?string $monthKey = null): ?int
    {
        $limit = $this->bandwidthLimitBytes();

        if ($limit === null) {
            return null;
        }

        return max($limit - $this->currentMonthBandwidthUsageBytes($monthKey), 0);
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
