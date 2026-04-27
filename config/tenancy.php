<?php

declare(strict_types=1);

use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    'central_domains' => array_values(array_unique(array_filter([
        '127.0.0.1',
        'localhost',
        env('APP_DOMAIN', config('app.domain')),
    ]))),

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,

        // ❌ DISABLED (causes "cache store does not support tagging")
        // Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,

        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),

        'template_tenant_connection' => env(
            'TENANCY_TEMPLATE_CONNECTION',
            env('DB_CONNECTION', 'mysql')
        ),

        'prefix' => env('TENANCY_DATABASE_PREFIX', 'tenant_'),
        'suffix' => '',

        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    // ⚠️ Keep this, but it's harmless now since cache bootstrapper is disabled
    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',

        'disks' => [
            'local',
            'public',
        ],

        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [],

    'routes' => true,

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],

    // Custom DB config for tenants
    'charset' => env('TENANCY_DB_CHARSET', 'utf8mb4'),
    'collation' => env('TENANCY_DB_COLLATION', 'utf8mb4_unicode_ci'),

    'tenant_host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'tenant_port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
    'tenant_username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'root')),
    'tenant_password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),

    'bandwidth' => [
        'flush_interval_seconds' => (int) env('TENANCY_BANDWIDTH_FLUSH_INTERVAL_SECONDS', 30),
        'flush_threshold_bytes' => (int) env('TENANCY_BANDWIDTH_FLUSH_THRESHOLD_BYTES', 65536),
    ],
];