<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        $isOwner = Auth::user()?->hasRole('Owner') ?? false;
        $customSections = $this->normalizeCustomSections(data_get($tenant->settings, 'dashboard.custom_sections'));
        $layout = $this->normalizeDashboardLayout(data_get($tenant->settings, 'dashboard.layout'), $customSections);
        $navigationPosition = $this->normalizeNavigationPosition(data_get($tenant->settings, 'dashboard.navigation.position', 'top'));
        $otaState = $isOwner ? $this->resolveOtaBannerState($tenant->settings ?? []) : ['visible' => false];

        return view('dashboard.index', [
            'dashboardLayout' => $layout,
            'availableWidgets' => $this->availableWidgets(),
            'customSections' => $customSections,
            'navigationPosition' => $navigationPosition,
            'canCustomizeDashboard' => $isOwner,
            'otaBanner' => $otaState,
        ]);
    }

    public function updateLayout(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->hasRole('Owner') ?? false, 403, 'Only the shop owner can customize the dashboard.');

        $validated = $request->validate([
            'layout' => ['required', 'array'],
            'layout.top' => ['required', 'array'],
            'layout.bottom' => ['nullable', 'array'],
            'layout.top.*' => ['string'],
            'layout.bottom.*' => ['string'],
            'navigation_position' => ['nullable', 'in:top,left,right'],
            'custom_sections' => ['nullable', 'array'],
            'custom_sections.*.id' => ['required', 'string'],
            'custom_sections.*.title' => ['required', 'string', 'max:80'],
            'custom_sections.*.content' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = tenant();
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $customSections = $this->normalizeCustomSections($validated['custom_sections'] ?? []);
        $layout = $this->normalizeDashboardLayout($validated['layout'] ?? null, $customSections);
        $navigationPosition = $this->normalizeNavigationPosition($validated['navigation_position'] ?? data_get($settings, 'dashboard.navigation.position', 'top'));

        data_set($settings, 'dashboard.layout', $layout);
        data_set($settings, 'dashboard.custom_sections', $customSections);
        data_set($settings, 'dashboard.navigation.position', $navigationPosition);

        $tenant->settings = $settings;
        $tenant->save();

        return response()->json([
            'message' => 'Dashboard layout saved.',
            'layout' => $layout,
            'custom_sections' => $customSections,
            'navigation_position' => $navigationPosition,
        ]);
    }

    public function resetLayout(): JsonResponse
    {
        abort_unless(Auth::user()?->hasRole('Owner') ?? false, 403, 'Only the shop owner can customize the dashboard.');

        $tenant = tenant();
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $defaultLayout = $this->defaultLayout();
        $navigationPosition = $this->normalizeNavigationPosition(data_get($settings, 'dashboard.navigation.position', 'top'));

        data_set($settings, 'dashboard.layout', $defaultLayout);
        data_set($settings, 'dashboard.custom_sections', []);
        data_set($settings, 'dashboard.navigation.position', $navigationPosition);

        $tenant->settings = $settings;
        $tenant->save();

        return response()->json([
            'message' => 'Dashboard layout reset to default.',
            'layout' => $defaultLayout,
            'custom_sections' => [],
            'navigation_position' => $navigationPosition,
        ]);
    }

    protected function availableWidgets(): array
    {
        return [
            'welcome' => [
                'label' => 'Welcome',
                'description' => 'Welcome message for your shop team.',
            ],
            'quick_actions' => [
                'label' => 'Quick Actions',
                'description' => 'Shortcuts to common operations.',
            ],
            'today_glance' => [
                'label' => 'Today at a Glance',
                'description' => 'Today metrics for products, sales, and users.',
            ],
            'plan_summary' => [
                'label' => 'Plan Summary',
                'description' => 'Current subscription and included features.',
            ],
        ];
    }

    protected function defaultLayout(): array
    {
        return [
            'top' => ['welcome', 'quick_actions'],
            'bottom' => ['today_glance', 'plan_summary'],
        ];
    }

    protected function normalizeDashboardLayout($layout, array $customSections = []): array
    {
        $default = $this->defaultLayout();
        $allowed = array_values(array_unique(array_merge(
            array_keys($this->availableWidgets()),
            array_column($customSections, 'id')
        )));

        $top = [];
        $bottom = [];

        if (is_array($layout)) {
            $top = is_array($layout['top'] ?? null) ? $layout['top'] : [];
            $bottom = is_array($layout['bottom'] ?? null) ? $layout['bottom'] : [];

            if (empty($top) && empty($bottom)) {
                $top = is_array($layout['left'] ?? null) ? $layout['left'] : [];
                $bottom = is_array($layout['right'] ?? null) ? $layout['right'] : [];
            }
        }

        $top = array_values(array_unique(array_filter($top, fn ($widget) => is_string($widget) && in_array($widget, $allowed, true))));
        $bottom = array_values(array_unique(array_filter($bottom, fn ($widget) => is_string($widget) && in_array($widget, $allowed, true))));

        $seen = [];
        $top = array_values(array_filter($top, function (string $widget) use (&$seen) {
            if (in_array($widget, $seen, true)) {
                return false;
            }

            $seen[] = $widget;
            return true;
        }));

        $bottom = array_values(array_filter($bottom, function (string $widget) use (&$seen) {
            if (in_array($widget, $seen, true)) {
                return false;
            }

            $seen[] = $widget;
            return true;
        }));

        if (empty($top) && empty($bottom)) {
            return $default;
        }

        return [
            'top' => $top,
            'bottom' => $bottom,
        ];
    }

    protected function normalizeCustomSections($sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $normalized = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $rawId = trim((string) ($section['id'] ?? ''));
            $title = trim((string) ($section['title'] ?? ''));
            $content = trim((string) ($section['content'] ?? ''));

            if ($title === '') {
                continue;
            }

            $idBody = Str::of($rawId)->replace('custom:', '')->slug()->value();
            if ($idBody === '') {
                $idBody = Str::random(8);
            }

            $id = 'custom:' . $idBody;

            if (isset($normalized[$id])) {
                continue;
            }

            $normalized[$id] = [
                'id' => $id,
                'title' => Str::limit($title, 80, ''),
                'content' => Str::limit($content, 500, ''),
            ];
        }

        return array_values($normalized);
    }

    protected function normalizeNavigationPosition($position): string
    {
        return in_array($position, ['top', 'left', 'right'], true) ? $position : 'top';
    }

    protected function resolveOtaBannerState(array $settings): array
    {
        $ota = data_get($settings, 'updates.ota', []);
        $latestRelease = trim((string) data_get($ota, 'latest_release', ''));
        $processedAt = data_get($ota, 'processed_at');
        $releaseUrl = trim((string) data_get($ota, 'release_url', ''));
        $currentVersion = trim((string) config('app.version', 'dev'));

        if ($latestRelease !== '' && strcasecmp(ltrim($latestRelease, 'v'), ltrim($currentVersion, 'v')) !== 0) {
            return [
                'visible' => true,
                'variant' => 'warning',
                'title' => 'Update available',
                'message' => $latestRelease,
                'processed_at' => $processedAt,
                'release_url' => $releaseUrl !== '' ? $releaseUrl : null,
                'action_label' => 'Update now',
            ];
        }

        if ($processedAt) {
            return [
                'visible' => true,
                'variant' => 'success',
                'title' => 'Last OTA processed',
                'message' => $processedAt,
                'release_url' => $releaseUrl !== '' ? $releaseUrl : null,
                'action_label' => 'View release',
            ];
        }

        return [
            'visible' => false,
            'variant' => 'neutral',
            'title' => null,
            'message' => null,
            'processed_at' => null,
            'release_url' => null,
            'action_label' => null,
        ];
    }
}