<?php

use App\Models\Tenant;
use App\Services\TenantDatabaseProvisioner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
