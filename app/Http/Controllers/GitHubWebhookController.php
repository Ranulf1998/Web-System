<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTenantOtaUpdateJob;
use App\Services\GitHubReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GitHubWebhookController extends Controller
{
    public function __invoke(Request $request, GitHubReleaseService $releaseService): JsonResponse
    {
        $secret = trim((string) config('version.webhook_secret', ''));
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $event = (string) $request->header('X-GitHub-Event', '');
        $payload = $request->getContent();

        if ($secret === '' || ! $this->isValidSignature($secret, $payload, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature.'], 401);
        }

        if ($event === 'ping') {
            return response()->json(['ok' => true, 'message' => 'Webhook received.']);
        }

        if ($event !== 'release' || $request->input('action') !== 'published') {
            return response()->json(['ok' => true, 'message' => 'Event ignored.']);
        }

        $incomingRepo = trim((string) $request->input('repository.full_name', ''));
        $configuredRepo = trim((string) config('version.github_repo', ''));

        if ($incomingRepo !== '' && $configuredRepo !== '' && strcasecmp($incomingRepo, $configuredRepo) !== 0) {
            return response()->json(['ok' => true, 'message' => 'Repository mismatch; ignored.']);
        }

        $releaseTag = trim((string) $request->input('release.tag_name', ''));
        $releaseUrl = trim((string) $request->input('release.html_url', ''));

        if ($releaseTag !== '') {
            ProcessTenantOtaUpdateJob::dispatch(
                releaseTag: $releaseTag,
                releaseUrl: $releaseUrl !== '' ? $releaseUrl : null
            );
        }

        Cache::forget($releaseService->cacheKey());

        return response()->json([
            'ok' => true,
            'message' => 'Latest release cache invalidated and OTA job queued.',
        ]);
    }

    private function isValidSignature(string $secret, string $payload, string $signature): bool
    {
        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
