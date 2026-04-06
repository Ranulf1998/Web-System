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
        $current = (string) config('app.version', 'dev');

        if ($repo === '') {
            return [
                'current_version' => $current,
                'latest_version' => null,
                'latest_url' => null,
                'update_available' => false,
            ];
        }

        return Cache::remember(
            "github_latest_release_{$repo}",
            now()->addMinutes((int) config('version.cache_minutes', 15)),
            function () use ($repo, $current) {
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
                $latest = (string) ($json['tag_name'] ?? '');

                return [
                    'current_version' => $current,
                    'latest_version' => $latest !== '' ? $latest : null,
                    'latest_url' => $json['html_url'] ?? null,
                    'update_available' => $latest !== '' && $latest !== $current,
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
}