<?php

use App\Models\Tenant;
use App\Services\GitHubReleaseService;
use App\Services\TenantDatabaseProvisioner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use App\Jobs\ProcessTenantOtaUpdateJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenants:backfill-database-settings {--subdomain=} {--dry-run}', function (TenantDatabaseProvisioner $provisioner) {
    $subdomain = $this->option('subdomain');
    $dryRun = (bool) $this->option('dry-run');

    $query = Tenant::query()->orderBy('id');

    if (is_string($subdomain) && $subdomain !== '') {
        $query->where('subdomain', $subdomain);
    }

    $tenants = $query->get();

    if ($tenants->isEmpty()) {
        $this->warn('No tenants found for the given criteria.');
        return;
    }

    $updated = 0;
    $skipped = 0;

    foreach ($tenants as $tenant) {
        /** @var \App\Models\Tenant $tenant */
        $settings = $tenant->settings;
        if (!is_array($settings)) {
            $settings = [];
        }

        $existingDatabase = data_get($settings, 'database.database');

        if (is_string($existingDatabase) && $existingDatabase !== '') {
            $skipped++;
            $this->line("Skipped [{$tenant->subdomain}] (already configured: {$existingDatabase})");
            continue;
        }

        $databaseName = $provisioner->generateDatabaseName((string) $tenant->subdomain);

        $databaseSettings = [
            'host' => config('tenancy.tenant_host'),
            'port' => config('tenancy.tenant_port'),
            'database' => $databaseName,
            'username' => config('tenancy.tenant_username'),
            'password' => config('tenancy.tenant_password'),
        ];

        data_set($settings, 'database', $databaseSettings);

        if ($dryRun) {
            $this->line("[DRY RUN] Would update [{$tenant->subdomain}] => {$databaseName}");
            $updated++;
            continue;
        }

        $tenant->settings = $settings;
        $tenant->save();

        $this->info("Updated [{$tenant->subdomain}] => {$databaseName}");
        $updated++;
    }

    $mode = $dryRun ? 'Dry run complete' : 'Backfill complete';
    $this->newLine();
    $this->info("{$mode}. Updated: {$updated}. Skipped: {$skipped}.");
})->purpose('Backfill settings.database for existing tenants');

Artisan::command('tenants:migrate-users {--subdomain=} {--dry-run} {--keep-central}', function () {
    $subdomain = $this->option('subdomain');
    $dryRun = (bool) $this->option('dry-run');
    $keepCentral = (bool) $this->option('keep-central');

    $query = Tenant::query()->orderBy('id');

    if (is_string($subdomain) && $subdomain !== '') {
        $query->where('subdomain', $subdomain);
    }

    $tenants = $query->get(['id', 'subdomain', 'settings']);

    if ($tenants->isEmpty()) {
        $this->warn('No tenants found for the given criteria.');
        return;
    }

    $totals = [
        'migrated_users' => 0,
        'skipped_tenants' => 0,
        'processed_tenants' => 0,
    ];

    foreach ($tenants as $tenant) {
        /** @var \App\Models\Tenant $tenant */
        $database = data_get($tenant->settings, 'database');

        if (! is_array($database) || empty($database['database'])) {
            $totals['skipped_tenants']++;
            $this->warn("Skipped [{$tenant->subdomain}] - tenant database is not configured.");
            continue;
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

        $this->line("Processing [{$tenant->subdomain}] ({$database['database']})...");

        if (! $dryRun) {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => database_path('migrations/tenant'),
                '--realpath' => true,
                '--force' => true,
            ]);
        }

        $centralUsers = DB::connection('central')
            ->table('users')
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('id')
            ->get();

        if ($centralUsers->isEmpty()) {
            $totals['processed_tenants']++;
            $this->line("  - No central tenant users to migrate for [{$tenant->subdomain}].");
            continue;
        }

        $userIds = $centralUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $rolesByUserId = DB::connection('central')
            ->table('model_has_roles as mhr')
            ->join('roles', 'roles.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', App\Models\User::class)
            ->whereIn('mhr.model_id', $userIds)
            ->where('roles.tenant_id', (int) $tenant->id)
            ->select('mhr.model_id', 'roles.name')
            ->get()
            ->groupBy('model_id')
            ->map(fn ($rows) => $rows->pluck('name')->unique()->values()->all());

        $directPermissionsByUserId = DB::connection('central')
            ->table('model_has_permissions as mhp')
            ->join('permissions', 'permissions.id', '=', 'mhp.permission_id')
            ->where('mhp.model_type', App\Models\User::class)
            ->whereIn('mhp.model_id', $userIds)
            ->select('mhp.model_id', 'permissions.name')
            ->get()
            ->groupBy('model_id')
            ->map(fn ($rows) => $rows->pluck('name')->unique()->values()->all());

        $tenantRoleRows = DB::connection('central')
            ->table('roles')
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('id')
            ->get(['id', 'name', 'guard_name']);

        $tenantRoleIds = $tenantRoleRows->pluck('id')->map(fn ($id) => (int) $id)->all();

        $rolePermissions = DB::connection('central')
            ->table('role_has_permissions as rhp')
            ->join('permissions', 'permissions.id', '=', 'rhp.permission_id')
            ->join('roles', 'roles.id', '=', 'rhp.role_id')
            ->when(! empty($tenantRoleIds), fn ($query) => $query->whereIn('rhp.role_id', $tenantRoleIds))
            ->select('roles.name as role_name', 'permissions.name as permission_name')
            ->get()
            ->groupBy('role_name')
            ->map(fn ($rows) => $rows->pluck('permission_name')->unique()->values()->all());

        $allPermissionNames = collect();
        $allPermissionNames = $allPermissionNames
            ->merge($rolePermissions->flatten(1))
            ->merge($directPermissionsByUserId->flatten(1))
            ->unique()
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values();

        if ($dryRun) {
            $this->line("  - [DRY RUN] Would migrate {$centralUsers->count()} users.");
            $this->line("  - [DRY RUN] Would sync {$tenantRoleRows->count()} roles and {$allPermissionNames->count()} permissions.");
            $totals['migrated_users'] += (int) $centralUsers->count();
            $totals['processed_tenants']++;
            continue;
        }

        foreach ($allPermissionNames as $permissionName) {
            DB::connection('tenant')->table('permissions')->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $tenantRoleIdByName = [];

        foreach ($tenantRoleRows as $roleRow) {
            DB::connection('tenant')->table('roles')->updateOrInsert(
                [
                    'tenant_id' => (int) $tenant->id,
                    'name' => (string) $roleRow->name,
                    'guard_name' => (string) $roleRow->guard_name,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $tenantRoleIdByName[(string) $roleRow->name] = (int) DB::connection('tenant')
                ->table('roles')
                ->where('tenant_id', (int) $tenant->id)
                ->where('name', (string) $roleRow->name)
                ->where('guard_name', (string) $roleRow->guard_name)
                ->value('id');
        }

        $permissionIdByName = DB::connection('tenant')
            ->table('permissions')
            ->whereIn('name', $allPermissionNames->all())
            ->pluck('id', 'name');

        foreach ($tenantRoleIdByName as $roleName => $roleId) {
            DB::connection('tenant')->table('role_has_permissions')->where('role_id', $roleId)->delete();

            $permissionIds = collect($rolePermissions->get($roleName, []))
                ->map(fn ($name) => $permissionIdByName[$name] ?? null)
                ->filter()
                ->unique()
                ->values();

            foreach ($permissionIds as $permissionId) {
                DB::connection('tenant')->table('role_has_permissions')->insert([
                    'permission_id' => (int) $permissionId,
                    'role_id' => (int) $roleId,
                ]);
            }
        }

        $tenantUserIdMap = [];

        foreach ($centralUsers as $centralUser) {
            $existingTenantUser = DB::connection('tenant')
                ->table('users')
                ->where('id', (int) $centralUser->id)
                ->orWhere('email', (string) $centralUser->email)
                ->first();

            $targetTenantUserId = $existingTenantUser
                ? (int) $existingTenantUser->id
                : (int) $centralUser->id;

            if ($existingTenantUser) {
                DB::connection('tenant')->table('users')
                    ->where('id', $targetTenantUserId)
                    ->update([
                        'tenant_id' => (int) $tenant->id,
                        'name' => (string) $centralUser->name,
                        'email' => (string) $centralUser->email,
                        'password' => (string) $centralUser->password,
                        'email_verified_at' => $centralUser->email_verified_at,
                        'remember_token' => $centralUser->remember_token,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::connection('tenant')->table('users')->insert([
                    'id' => (int) $centralUser->id,
                    'tenant_id' => (int) $tenant->id,
                    'name' => (string) $centralUser->name,
                    'email' => (string) $centralUser->email,
                    'password' => (string) $centralUser->password,
                    'email_verified_at' => $centralUser->email_verified_at,
                    'remember_token' => $centralUser->remember_token,
                    'created_at' => $centralUser->created_at,
                    'updated_at' => $centralUser->updated_at,
                ]);
            }

            $tenantUserIdMap[(int) $centralUser->id] = $targetTenantUserId;
        }

        foreach ($tenantUserIdMap as $oldUserId => $newUserId) {
            if ($oldUserId === $newUserId) {
                continue;
            }

            foreach (['orders', 'activity_logs'] as $table) {
                if (Schema::connection('tenant')->hasTable($table) && Schema::connection('tenant')->hasColumn($table, 'user_id')) {
                    DB::connection('tenant')->table($table)->where('user_id', $oldUserId)->update(['user_id' => $newUserId]);
                }
            }
        }

        foreach ($tenantUserIdMap as $oldUserId => $tenantUserId) {
            DB::connection('tenant')->table('model_has_roles')
                ->where('model_type', App\Models\User::class)
                ->where('model_id', $tenantUserId)
                ->delete();

            DB::connection('tenant')->table('model_has_permissions')
                ->where('model_type', App\Models\User::class)
                ->where('model_id', $tenantUserId)
                ->delete();

            $roleNames = $rolesByUserId->get($oldUserId, []);
            foreach ($roleNames as $roleName) {
                $roleId = $tenantRoleIdByName[$roleName] ?? null;
                if (! $roleId) {
                    continue;
                }

                DB::connection('tenant')->table('model_has_roles')->insert([
                    'role_id' => (int) $roleId,
                    'model_type' => App\Models\User::class,
                    'model_id' => (int) $tenantUserId,
                ]);
            }

            $directPermissionNames = $directPermissionsByUserId->get($oldUserId, []);
            foreach ($directPermissionNames as $permissionName) {
                $permissionId = $permissionIdByName[$permissionName] ?? null;
                if (! $permissionId) {
                    continue;
                }

                DB::connection('tenant')->table('model_has_permissions')->insert([
                    'permission_id' => (int) $permissionId,
                    'model_type' => App\Models\User::class,
                    'model_id' => (int) $tenantUserId,
                ]);
            }
        }

        if (! $keepCentral) {
            DB::connection('central')->table('model_has_roles')
                ->where('model_type', App\Models\User::class)
                ->whereIn('model_id', $userIds)
                ->delete();

            DB::connection('central')->table('model_has_permissions')
                ->where('model_type', App\Models\User::class)
                ->whereIn('model_id', $userIds)
                ->delete();

            DB::connection('central')->table('sessions')->whereIn('user_id', $userIds)->delete();
            DB::connection('central')->table('users')->whereIn('id', $userIds)->delete();
        }

        $totals['migrated_users'] += (int) $centralUsers->count();
        $totals['processed_tenants']++;

        $this->info("  - Migrated {$centralUsers->count()} users for [{$tenant->subdomain}].");
    }

    $mode = $dryRun ? 'Dry run complete' : 'User migration complete';
    $this->newLine();
    $this->info("{$mode}. Tenants processed: {$totals['processed_tenants']}, tenants skipped: {$totals['skipped_tenants']}, users migrated: {$totals['migrated_users']}. ");
    $this->line('Tip: run with --keep-central first for verification, then run again without --keep-central to finalize cleanup.');
})->purpose('Migrate tenant users/roles from central DB to per-tenant databases');

Artisan::command('updates:sync-latest {--publish : Queue tenant OTA notifications when a newer release is detected} {--force : Ignore the last seen release guard}', function (GitHubReleaseService $releaseService) {
    $enabled = filter_var((string) env('RELEASE_SYNC_ENABLED', 'true'), FILTER_VALIDATE_BOOL);

    if (! $enabled) {
        $this->warn('Release sync is disabled by RELEASE_SYNC_ENABLED=false.');
        return;
    }

    $latest = $releaseService->latest(false);
    $latestTag = trim((string) ($latest['latest_version'] ?? ''));

    if ($latestTag === '') {
        $this->warn('No latest release was returned from GitHub.');
        return;
    }

    $cacheStore = Cache::store((string) config('version.cache_store', 'file'));
    $lastSeen = trim((string) $cacheStore->get('updates.last_seen_release', ''));
    $force = (bool) $this->option('force');

    if (! $force && $lastSeen !== '' && strcasecmp($lastSeen, $latestTag) === 0) {
        $this->line('No new release detected. Last seen: ' . $lastSeen);
        return;
    }

    $cacheStore->forever('updates.last_seen_release', $latestTag);
    Cache::forget($releaseService->cacheKey());

    $this->info('Detected latest release: ' . $latestTag);

    if ((bool) $this->option('publish')) {
        ProcessTenantOtaUpdateJob::dispatch(
            releaseTag: $latestTag,
            releaseUrl: $latest['latest_url'] ?? null,
            applyImmediately: false,
        );

        $this->info('Tenant OTA metadata update job dispatched.');
        return;
    }

    $this->line('Run with --publish to queue tenant notifications/metadata sync.');
})->purpose('Sync latest GitHub release metadata and optionally publish OTA update notices to tenants');

if (filter_var((string) env('RELEASE_SYNC_ENABLED', 'true'), FILTER_VALIDATE_BOOL)) {
    Schedule::command('updates:sync-latest --publish')
        ->cron((string) env('RELEASE_SYNC_CRON', '*/15 * * * *'))
        ->withoutOverlapping();
}
