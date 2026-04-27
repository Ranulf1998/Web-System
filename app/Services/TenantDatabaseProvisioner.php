<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantDatabaseProvisioner
{
    public function generateDatabaseName(string $subdomain): string
    {
        $prefix = (string) config('tenancy.database.prefix', 'tenant_');

        return $prefix . $subdomain;
    }

    public function createDatabase(string $databaseName): void
    {
        $this->assertValidDatabaseName($databaseName);

        $charset = config('tenancy.charset', 'utf8mb4');
        $collation = config('tenancy.collation', 'utf8mb4_unicode_ci');

        DB::connection('central')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}"
        );
    }

    public function dropDatabase(string $databaseName): void
    {
        $this->assertValidDatabaseName($databaseName);

        DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }

    public function runTenantMigrations(Tenant $tenant): void
    {
        $database = data_get($tenant->settings, 'database');

        if (!is_array($database) || empty($database['database'])) {
            throw new RuntimeException('Tenant database settings are missing.');
        }

        $migrationPath = database_path('migrations/tenant');

        if (!is_dir($migrationPath)) {
            throw new RuntimeException("Tenant migration path not found: {$migrationPath}");
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

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');

        $exitCode = Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Tenant migration failed: ' . trim((string) Artisan::output()));
        }
    }

    protected function assertValidDatabaseName(string $databaseName): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
            throw new RuntimeException('Invalid tenant database name.');
        }
    }
}