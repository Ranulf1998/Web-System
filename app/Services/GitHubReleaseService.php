<?php


namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubReleaseService
{
    public function latest(): array
    {
        $repo = $this->normalizeRepo((string) config('version.github_repo'));
        $current = trim((string) config('app.version', 'dev'));
        $current = $current !== '' ? $current : 'dev';
        $normalizedCurrent = $this->normalizeVersion($current);

        if ($repo === '') {
            return [
                'current_version' => $current,
                'latest_version' => null,
                'latest_url' => null,
                'update_available' => false,
            ];
        }

        return Cache::remember(
            $this->cacheKey($repo),
            now()->addMinutes((int) config('version.cache_minutes', 15)),
            function () use ($repo, $current, $normalizedCurrent) {
                $verify = (bool) config('version.verify_ssl', app()->isProduction());
                $request = Http::acceptJson()->timeout(10)->withOptions(['verify' => $verify]);

                $token = trim((string) config('version.github_token'));
                if ($token !== '') {
                    $request = $request->withToken($token);
                }

                try {
                    $response = $request->get("https://api.github.com/repos/{$repo}/releases/latest");
                } catch (\Throwable $exception) {
                    Log::warning('Unable to fetch latest GitHub release.', [
                        'repo' => $repo,
                        'error' => $exception->getMessage(),
                    ]);

                    return [
                        'current_version' => $current,
                        'latest_version' => null,
                        'latest_url' => null,
                        'update_available' => false,
                    ];
                }

                if (! $response->successful()) {
                    return [
                        'current_version' => $current,
                        'latest_version' => null,
                        'latest_url' => null,
                        'update_available' => false,
                    ];
                }

                $json = $response->json();
                $latest = trim((string) ($json['tag_name'] ?? ''));
                $normalizedLatest = $this->normalizeVersion($latest);

                $updateAvailable = false;
                if ($normalizedLatest !== '' && $normalizedCurrent !== '') {
                    if ($this->isSemanticVersion($normalizedLatest) && $this->isSemanticVersion($normalizedCurrent)) {
                        $updateAvailable = version_compare($normalizedLatest, $normalizedCurrent, '>');
                    } else {
                        $updateAvailable = $normalizedLatest !== $normalizedCurrent;
                    }
                }

                return [
                    'current_version' => $current,
                    'latest_version' => $latest !== '' ? $latest : null,
                    'latest_url' => $json['html_url'] ?? null,
                    'update_available' => $updateAvailable,
                ];
            }
        );
    }

    protected function normalizeRepo(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (preg_match('#^https?://github\.com/([^/]+)/([^/]+?)(?:\.git)?/?$#i', $trimmed, $matches) === 1) {
            return $matches[1] . '/' . $matches[2];
        }

        return trim($trimmed, '/');
    }

    public function cacheKey(?string $repo = null): string
    {
        $resolvedRepo = $this->normalizeRepo((string) ($repo ?? config('version.github_repo', '')));

        return "github_latest_release_{$resolvedRepo}";
    }

    protected function normalizeVersion(string $value): string
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return '';
        }

        $normalized = ltrim($normalized, 'v');

        if (preg_match('/\d+(?:\.\d+)+(?:[-+][0-9a-z.-]+)?/i', $normalized, $matches) === 1) {
            return $matches[0];
        }

        return $normalized;
    }

    protected function isSemanticVersion(string $value): bool
    {
        return preg_match('/^\d+(?:\.\d+){1,3}(?:[-+][0-9a-z.-]+)?$/i', $value) === 1;
    }
}