<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SyncApplicationFromGitHubJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $releaseTag,
        public readonly ?string $releaseUrl = null,
    ) {
    }

    public function handle(): void
    {
        if (! (bool) config('version.updater_enabled', false)) {
            $this->markFailedStatus('Platform updater is disabled. Set UPDATER_ENABLED=true to allow sync.');
            return;
        }

        $lock = Cache::lock('platform:update-sync', (int) config('version.updater_lock_seconds', 3600));

        if (! $lock->get()) {
            $this->markFailedStatus('Another platform update is currently running. Please try again in a few minutes.');
            return;
        }

        try {
            $this->markRunningStatus();

            $process = $this->buildProcess();
            $process->run();

            if (! $process->isSuccessful()) {
                $output = trim($process->getErrorOutput() . "\n" . $process->getOutput());
                $output = mb_substr($output, 0, 3000);

                throw new \RuntimeException($output !== '' ? $output : 'Update script failed with no output.');
            }

            $this->markSuccessStatus();

            Cache::store((string) config('version.cache_store', 'file'))
                ->forever('updates.last_seen_release', $this->releaseTag);
        } catch (\Throwable $exception) {
            Log::error('Platform update sync job failed.', [
                'tenant_id' => $this->tenantId,
                'release_tag' => $this->releaseTag,
                'error' => $exception->getMessage(),
            ]);

            $this->markFailedStatus($exception->getMessage());
            throw $exception;
        } finally {
            optional($lock)->release();
        }
    }

    private function buildProcess(): Process
    {
        $branch = (string) config('version.updater_branch', 'main');
        $timeout = (int) config('version.updater_timeout_seconds', 1800);

        if (PHP_OS_FAMILY === 'Windows') {
            $script = base_path('scripts/update-and-sync.ps1');
            $command = [
                'powershell',
                '-NoProfile',
                '-ExecutionPolicy',
                'Bypass',
                '-File',
                $script,
                '-ReleaseTag',
                $this->releaseTag,
                '-Branch',
                $branch,
            ];

            $process = new Process($command, base_path());
            $process->setTimeout($timeout);

            return $process;
        }

        $script = base_path('scripts/update-and-sync.sh');
        $command = ['bash', $script, $this->releaseTag];

        $process = new Process($command, base_path(), [
            'BRANCH' => $branch,
        ]);
        $process->setTimeout($timeout);

        return $process;
    }

    private function markRunningStatus(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        data_set($settings, 'updates.ota.status', 'running');
        data_set($settings, 'updates.ota.running_at', now()->toIso8601String());

        $tenant->forceFill(['settings' => $settings])->save();
    }

    private function markSuccessStatus(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        data_set($settings, 'updates.ota.latest_release', $this->releaseTag);
        data_set($settings, 'updates.ota.current_version', $this->releaseTag);
        data_set($settings, 'updates.ota.release_url', $this->releaseUrl);
        data_set($settings, 'updates.ota.status', 'applied');
        data_set($settings, 'updates.ota.applied_at', now()->toIso8601String());
        data_forget($settings, 'updates.ota.last_error');

        $tenant->forceFill(['settings' => $settings])->save();
    }

    private function markFailedStatus(string $error): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        data_set($settings, 'updates.ota.status', 'failed');
        data_set($settings, 'updates.ota.failed_at', now()->toIso8601String());
        data_set($settings, 'updates.ota.last_error', mb_substr(trim($error), 0, 1000));

        $tenant->forceFill(['settings' => $settings])->save();
    }
}
