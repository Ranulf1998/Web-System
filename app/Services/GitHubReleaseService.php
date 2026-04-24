<?php


namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubReleaseService
{
    public function releases(int $limit = 10, bool $useCache = true): array
    {
        $repo = $this->normalizeRepo((string) config('version.github_repo'));
        $current = trim((string) config('app.version', 'dev'));
        $current = $current !== '' ? $current : 'dev';

        if ($repo === '') {
            return [];
        }

        $cacheKey = $this->cacheKey($repo) . '_list_' . max($limit, 1);
        $fetchReleases = function () use ($repo, $current, $limit) {
            return $this->fetchReleasesFromGithub($repo, $current, $limit);
        };

        if (! $useCache) {
            return $fetchReleases();
        }

        return Cache::store((string) config('version.cache_store', 'file'))->remember(
            $cacheKey,
            now()->addMinutes((int) config('version.cache_minutes', 15)),
            $fetchReleases
        );
    }

    public function latest(bool $useCache = true): array
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
                'latest_download_url' => null,
                'update_available' => false,
            ];
        }

        if (! $useCache) {
            return $this->resolveLatestVersion($current, $normalizedCurrent);
        }

        return Cache::store((string) config('version.cache_store', 'file'))->remember(
            $this->cacheKey($repo),
            now()->addMinutes((int) config('version.cache_minutes', 15)),
            function () use ($current, $normalizedCurrent) {
                return $this->resolveLatestVersion($current, $normalizedCurrent);
            }
        );
    }

    protected function fetchReleasesFromGithub(string $repo, string $current, int $limit): array
    {
        $verify = (bool) config('version.verify_ssl', app()->isProduction());
        $request = Http::acceptJson()->timeout(10)->withOptions(['verify' => $verify]);

        $token = trim((string) config('version.github_token'));
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->get("https://api.github.com/repos/{$repo}/releases", [
                'per_page' => max($limit, 1),
                'exclude_prereleases' => false,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Unable to fetch GitHub release list.', [
                'repo' => $repo,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json() ?? [])
            ->take(max($limit, 1))
            ->map(function (array $release) use ($current) {
                $tag = trim((string) ($release['tag_name'] ?? ''));
                $normalizedTag = $this->normalizeVersion($tag);
                $normalizedCurrent = $this->normalizeVersion($current);

                $updateAvailable = false;
                if ($normalizedTag !== '' && $normalizedCurrent !== '') {
                    if ($this->isSemanticVersion($normalizedTag) && $this->isSemanticVersion($normalizedCurrent)) {
                        $updateAvailable = version_compare($normalizedTag, $normalizedCurrent, '>');
                    } else {
                        $updateAvailable = $normalizedTag !== $normalizedCurrent;
                    }
                }

                return [
                    'name' => trim((string) ($release['name'] ?? '')) ?: $tag,
                    'tag_name' => $tag,
                    'html_url' => $release['html_url'] ?? null,
                    'zipball_url' => $release['zipball_url'] ?? null,
                    'published_at' => $release['published_at'] ?? null,
                    'prerelease' => (bool) ($release['prerelease'] ?? false),
                    'draft' => (bool) ($release['draft'] ?? false),
                    'update_available' => $updateAvailable,
                ];
            })
            ->filter(fn (array $release) => ! empty($release['tag_name']))
            ->values()
            ->all();
    }

    protected function resolveLatestVersion(string $current, string $normalizedCurrent): array
    {
        $latestRelease = $this->resolveLatestRelease($this->releases(30, false));

        if (! is_array($latestRelease)) {
            return [
                'current_version' => $current,
                'latest_version' => null,
                'latest_url' => null,
                'latest_download_url' => null,
                'update_available' => false,
            ];
        }

        $latest = trim((string) ($latestRelease['tag_name'] ?? ''));
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
            'latest_url' => $latestRelease['html_url'] ?? null,
            'latest_download_url' => $latestRelease['zipball_url'] ?? null,
            'update_available' => $updateAvailable,
        ];
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

    protected function resolveLatestRelease(array $releases): ?array
    {
        $releases = array_values(array_filter($releases, fn ($release) => is_array($release)));

        if ($releases === []) {
            return null;
        }

        foreach ($releases as $release) {
            if (! empty($release['tag_name']) && empty($release['draft']) && empty($release['prerelease'])) {
                return $release;
            }
        }

        return $releases[0] ?? null;
    }
}