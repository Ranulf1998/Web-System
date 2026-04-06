<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackTenantBandwidthUsage
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = tenant();
        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        $freshTenant = Tenant::on('central')->find($tenant->getKey());
        if (! $freshTenant) {
            return $next($request);
        }

        $settings = is_array($freshTenant->settings) ? $freshTenant->settings : [];
        $monthKey = now()->format('Y-m');
        $monthlyUsage = data_get($settings, 'usage.bandwidth_monthly', []);
        if (! is_array($monthlyUsage)) {
            $monthlyUsage = [];
        }

        $planLimitBytes = $freshTenant->bandwidthLimitBytes();
        $currentMonthBytes = max((int) ($monthlyUsage[$monthKey] ?? 0), 0);

        if ($planLimitBytes !== null) {
            if ($currentMonthBytes > $planLimitBytes && ! $this->shouldBypassHardBlock($request)) {
                return $this->buildHardBlockResponse($request, $freshTenant, $monthKey, $currentMonthBytes, $planLimitBytes);
            }

            if ($this->shouldShowSoftWarning($currentMonthBytes, $planLimitBytes) && $request->hasSession()) {
                $request->session()->flash(
                    'bandwidth_warning',
                    'Monthly bandwidth usage is high: '.$this->formatBytes($currentMonthBytes).' of '.$this->formatBytes($planLimitBytes).' used.'
                );
            }
        }

        $response = $next($request);

        $requestBytes = (int) $request->server('CONTENT_LENGTH', 0);
        $responseBytes = $this->resolveResponseBytes($response);
        $totalBytes = max($requestBytes, 0) + max($responseBytes, 0);

        if ($totalBytes < 1) {
            return $response;
        }

        $freshTenant = Tenant::on('central')->find($tenant->getKey());
        if (! $freshTenant) {
            return $response;
        }

        $settings = is_array($freshTenant->settings) ? $freshTenant->settings : [];
        $currentBytes = (int) data_get($settings, 'usage.bandwidth_bytes', 0);
        $monthKey = now()->format('Y-m');
        $monthlyUsage = data_get($settings, 'usage.bandwidth_monthly', []);

        if (! is_array($monthlyUsage)) {
            $monthlyUsage = [];
        }

        $monthlyUsage[$monthKey] = max((int) ($monthlyUsage[$monthKey] ?? 0), 0) + $totalBytes;

        ksort($monthlyUsage);
        if (count($monthlyUsage) > 12) {
            $monthlyUsage = array_slice($monthlyUsage, -12, null, true);
        }

        $planLimitBytes = $freshTenant->bandwidthLimitBytes();
        $currentMonthBytes = max((int) ($monthlyUsage[$monthKey] ?? 0), 0);

        data_set($settings, 'usage.bandwidth_bytes', max($currentBytes, 0) + $totalBytes);
        data_set($settings, 'usage.bandwidth_monthly', $monthlyUsage);
        data_set($settings, 'usage.bandwidth_current_month', $monthKey);
        data_set($settings, 'usage.bandwidth_current_month_bytes', $currentMonthBytes);
        data_set($settings, 'usage.bandwidth_limit_bytes', $planLimitBytes);
        data_set($settings, 'usage.bandwidth_limit_exceeded', $planLimitBytes !== null ? $currentMonthBytes > $planLimitBytes : false);
        data_set($settings, 'usage.bandwidth.last_recorded_at', now()->toIso8601String());

        $freshTenant->settings = $settings;
        $freshTenant->save();

        return $response;
    }

    protected function resolveResponseBytes($response): int
    {
        $headerValue = $response->headers->get('Content-Length');
        if (is_numeric($headerValue)) {
            return (int) $headerValue;
        }

        $content = method_exists($response, 'getContent') ? $response->getContent() : null;
        if (is_string($content)) {
            return strlen($content);
        }

        return 0;
    }

    protected function shouldShowSoftWarning(int $currentMonthBytes, int $planLimitBytes): bool
    {
        if ($planLimitBytes <= 0 || $currentMonthBytes <= 0) {
            return false;
        }

        return $currentMonthBytes >= (int) floor($planLimitBytes * 0.9)
            && $currentMonthBytes <= $planLimitBytes;
    }

    protected function shouldBypassHardBlock(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return false;
        }

        return in_array($routeName, [
            'tenant.logout',
            'support-tickets.create',
            'support-tickets.store',
        ], true);
    }

    protected function buildHardBlockResponse(Request $request, Tenant $tenant, string $monthKey, int $currentMonthBytes, int $planLimitBytes): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Monthly bandwidth limit exceeded.',
                'month' => $monthKey,
                'usage_bytes' => $currentMonthBytes,
                'limit_bytes' => $planLimitBytes,
                'usage' => $this->formatBytes($currentMonthBytes),
                'limit' => $this->formatBytes($planLimitBytes),
            ], 429);
        }

        return response()->view('tenant.bandwidth-limit', [
            'tenant' => $tenant,
            'monthKey' => $monthKey,
            'usageLabel' => $this->formatBytes($currentMonthBytes),
            'limitLabel' => $this->formatBytes($planLimitBytes),
        ], 429);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max($bytes, 0);
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format((float) $value, 2).' '.$units[$unitIndex];
    }
}
