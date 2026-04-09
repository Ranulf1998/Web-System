<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Notifications\TenantOtaUpdateAvailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessTenantOtaUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly string $releaseTag,
        public readonly ?string $releaseUrl = null
    ) {
    }

    public function handle(): void
    {
        Tenant::query()->chunkById(100, function ($tenants) {
            foreach ($tenants as $tenant) {
                $this->processTenant($tenant);
            }
        });
    }

    private function processTenant(Tenant $tenant): void
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $registrationStatus = strtolower(trim((string) data_get($settings, 'status.registration', 'approved')));

        if ($registrationStatus !== 'approved') {
            return;
        }

        $suspendedAt = trim((string) data_get($settings, 'status.suspended_at', ''));
        if ($suspendedAt !== '') {
            return;
        }

        data_set($settings, 'updates.ota.latest_release', $this->releaseTag);
        data_set($settings, 'updates.ota.release_url', $this->releaseUrl);
        data_set($settings, 'updates.ota.status', 'processed');
        data_set($settings, 'updates.ota.processed_at', now()->toIso8601String());

        $ownerName = trim((string) data_get($settings, 'onboarding.owner.name', ''));
        $ownerEmail = trim((string) data_get($settings, 'onboarding.owner.email', ''));

        if ($ownerEmail !== '') {
            try {
                Notification::route('mail', $ownerEmail)
                    ->notify(new TenantOtaUpdateAvailable(
                        releaseTag: $this->releaseTag,
                        releaseUrl: $this->releaseUrl,
                        tenantName: $ownerName !== '' ? $ownerName : $tenant->name
                    ));

                data_set($settings, 'updates.ota.notified_at', now()->toIso8601String());
                data_set($settings, 'updates.ota.notified_email', $ownerEmail);
            } catch (\Throwable $exception) {
                data_set($settings, 'updates.ota.notification_error', $exception->getMessage());

                Log::warning('Failed tenant OTA notification.', [
                    'tenant_id' => $tenant->id,
                    'release' => $this->releaseTag,
                    'email' => $ownerEmail,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $tenant->forceFill(['settings' => $settings])->save();
    }
}
