<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            (function () {
                const storageKey = 'tenant-theme';

                try {
                    const storedTheme = localStorage.getItem(storageKey);
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (_) {
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .tenant-theme {
                --tenant-bg: #f8fafc;
                --tenant-surface: #ffffff;
                --tenant-surface-muted: #f8fafc;
                --tenant-border: #e2e8f0;
                --tenant-text: #0f172a;
                --tenant-text-muted: #64748b;
                background-color: var(--tenant-bg);
                color: var(--tenant-text);
                color-scheme: light;
            }

            html.dark .tenant-theme {
                --tenant-bg: #020617;
                --tenant-surface: #0f172a;
                --tenant-surface-muted: #111827;
                --tenant-border: #334155;
                --tenant-text: #e2e8f0;
                --tenant-text-muted: #cbd5e1;
                --tenant-text-soft: #94a3b8;
                background-color: var(--tenant-bg);
                color: var(--tenant-text);
                color-scheme: dark;
            }

            html.dark .tenant-theme [class*="bg-white"],
            html.dark .tenant-theme [class*="bg-gray-50"],
            html.dark .tenant-theme [class*="bg-slate-50"],
            html.dark .tenant-theme [class*="bg-zinc-50"] {
                background-color: var(--tenant-surface) !important;
            }

            html.dark .tenant-theme [class*="bg-gray-100"],
            html.dark .tenant-theme [class*="bg-slate-100"],
            html.dark .tenant-theme [class*="bg-zinc-100"],
            html.dark .tenant-theme [class*="bg-zinc-200"] {
                background-color: var(--tenant-surface-muted) !important;
            }

            html.dark .tenant-theme [class*="text-gray-900"],
            html.dark .tenant-theme [class*="text-gray-800"],
            html.dark .tenant-theme [class*="text-slate-900"],
            html.dark .tenant-theme [class*="text-slate-800"],
            html.dark .tenant-theme [class*="text-zinc-950"],
            html.dark .tenant-theme [class*="text-zinc-900"],
            html.dark .tenant-theme [class*="text-zinc-800"] {
                color: #f8fafc !important;
            }

            html.dark .tenant-theme [class*="text-gray-700"],
            html.dark .tenant-theme [class*="text-gray-600"],
            html.dark .tenant-theme [class*="text-slate-700"],
            html.dark .tenant-theme [class*="text-slate-600"],
            html.dark .tenant-theme [class*="text-zinc-700"],
            html.dark .tenant-theme [class*="text-zinc-600"] {
                color: var(--tenant-text-muted) !important;
            }

            html.dark .tenant-theme [class*="text-gray-500"],
            html.dark .tenant-theme [class*="text-gray-400"],
            html.dark .tenant-theme [class*="text-slate-500"],
            html.dark .tenant-theme [class*="text-slate-400"],
            html.dark .tenant-theme [class*="text-zinc-500"],
            html.dark .tenant-theme [class*="text-zinc-400"],
            html.dark .tenant-theme [class*="text-zinc-300"] {
                color: var(--tenant-text-soft) !important;
            }

            html.dark .tenant-theme [class*="border-gray-100"],
            html.dark .tenant-theme [class*="border-gray-200"],
            html.dark .tenant-theme [class*="border-gray-300"],
            html.dark .tenant-theme [class*="border-slate-100"],
            html.dark .tenant-theme [class*="border-slate-200"],
            html.dark .tenant-theme [class*="border-slate-300"],
            html.dark .tenant-theme [class*="border-zinc-100"],
            html.dark .tenant-theme [class*="border-zinc-200"],
            html.dark .tenant-theme [class*="border-zinc-300"] {
                border-color: var(--tenant-border) !important;
            }

            html.dark .tenant-theme [class*="hover:bg-gray-50"]:hover,
            html.dark .tenant-theme [class*="hover:bg-gray-100"]:hover,
            html.dark .tenant-theme [class*="hover:bg-slate-50"]:hover,
            html.dark .tenant-theme [class*="hover:bg-slate-100"]:hover,
            html.dark .tenant-theme [class*="hover:bg-zinc-50"]:hover,
            html.dark .tenant-theme [class*="hover:bg-zinc-100"]:hover {
                background-color: color-mix(in srgb, var(--tenant-surface) 80%, white) !important;
            }

            html.dark .tenant-theme input,
            html.dark .tenant-theme select,
            html.dark .tenant-theme textarea {
                background-color: #0b1220 !important;
                color: #e2e8f0 !important;
                border-color: var(--tenant-border) !important;
            }

            html.dark .tenant-theme input::placeholder,
            html.dark .tenant-theme textarea::placeholder {
                color: #64748b !important;
            }

            html.dark .tenant-theme .dashboard-title,
            html.dark .tenant-theme .dashboard-card-title,
            html.dark .tenant-theme .stat-value,
            html.dark .tenant-theme .modal-title {
                color: #000000 !important;
            }

            html.dark .tenant-theme .dashboard-subtitle,
            html.dark .tenant-theme .dashboard-card-body,
            html.dark .tenant-theme .dashboard-section-title,
            html.dark .tenant-theme .dashboard-chip,
            html.dark .tenant-theme .stat-label,
            html.dark .tenant-theme .stat-meta,
            html.dark .tenant-theme .modal-close,
            html.dark .tenant-theme .modal-list {
                color: #000000 !important;
            }

            html.dark .tenant-theme .action-card {
                color: #000000 !important;
            }

            html.dark .tenant-theme > .min-h-screen > header [class*="text-gray-900"],
            html.dark .tenant-theme > .min-h-screen > header [class*="text-gray-800"],
            html.dark .tenant-theme > .min-h-screen > header [class*="text-slate-900"],
            html.dark .tenant-theme > .min-h-screen > header [class*="text-slate-800"],
            html.dark .tenant-theme > .min-h-screen > header [class*="text-zinc-950"],
            html.dark .tenant-theme > .min-h-screen > header [class*="text-zinc-900"] {
                color: #000000 !important;
            }

            html.dark .tenant-theme > .min-h-screen > header .dashboard-title,
            html.dark .tenant-theme > .min-h-screen > header .dashboard-subtitle {
                color: #f8fafc !important;
            }

            html.dark .tenant-theme header .dashboard-header .dashboard-title,
            html.dark .tenant-theme header .dashboard-header .dashboard-subtitle {
                color: #f8fafc !important;
            }

            .tenant-theme-toggle {
                position: fixed;
                right: 1rem;
                bottom: 1rem;
                z-index: 60;
                border: 1px solid #d1d5db;
                background-color: #ffffff;
                color: #374151;
                border-radius: 9999px;
                padding: 0.55rem 0.95rem;
                font-size: 0.875rem;
                font-weight: 600;
                line-height: 1;
                box-shadow: 0 10px 25px -15px rgba(15, 23, 42, 0.5);
                transition: all 150ms ease;
            }

            .tenant-theme-toggle:hover {
                background-color: #f8fafc;
            }

            html.dark .tenant-theme-toggle {
                border-color: #334155;
                background-color: #0f172a;
                color: #e2e8f0;
            }

            html.dark .tenant-theme-toggle:hover {
                background-color: #111827;
            }
        </style>
    </head>
    @php
        $tenant = app()->bound('tenant') ? tenant() : null;
        $branding = $tenant?->settings['branding'] ?? [];
        $brandPrimary = $branding['primary'] ?? '#0f766e';
        $brandAccent = $branding['accent'] ?? '#f59e0b';
        $brandBackground = $branding['background'] ?? '#f3f4f6';
        $navPosition = data_get($tenant?->settings, 'dashboard.navigation.position', data_get($tenant?->settings, 'navigation.position', 'top'));
        if (! in_array($navPosition, ['top', 'left', 'right'], true)) {
            $navPosition = 'top';
        }
    @endphp
    <body class="tenant-theme font-sans antialiased transition-colors duration-300" style="font-family: 'Poppins', sans-serif; --brand-primary: {{ $brandPrimary }}; --brand-accent: {{ $brandAccent }}; --brand-background: {{ $brandBackground }};">
        <div class="min-h-screen brand-background">
            @if ($navPosition === 'top')
                @include('layouts.navigation')

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                @if (session('bandwidth_warning'))
                    <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ session('bandwidth_warning') }}
                        </div>
                    </div>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            @else
                @include('layouts.navigation', ['mobileOnly' => true])

                <div class="sm:flex sm:min-h-screen {{ $navPosition === 'right' ? 'sm:flex-row-reverse' : '' }}">
                    @include('layouts.tenant-sidebar', [
                        'sidebarBorderClass' => $navPosition === 'right' ? 'border-l' : 'border-r',
                    ])

                    <div class="min-w-0 flex-1">
                        <!-- Page Heading -->
                        @isset($header)
                            <header class="bg-white shadow sm:shadow-none">
                                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            </header>
                        @endisset

                        @if (session('bandwidth_warning'))
                            <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    {{ session('bandwidth_warning') }}
                                </div>
                            </div>
                        @endif

                        <!-- Page Content -->
                        <main>
                            {{ $slot }}
                        </main>
                    </div>
                </div>
            @endif

            @include('layouts.logout-confirm')
        </div>

        <button type="button" class="tenant-theme-toggle" data-tenant-theme-toggle aria-pressed="false">
            <span data-tenant-theme-label>Dark mode</span>
        </button>

        <script>
            (function () {
                const storageKey = 'tenant-theme';
                const root = document.documentElement;
                const toggleButton = document.querySelector('[data-tenant-theme-toggle]');
                const toggleLabel = document.querySelector('[data-tenant-theme-label]');

                if (!toggleButton || !toggleLabel) {
                    return;
                }

                const setTheme = (theme, shouldPersist = true) => {
                    const nextTheme = theme === 'dark' ? 'dark' : 'light';

                    root.classList.toggle('dark', nextTheme === 'dark');

                    if (shouldPersist) {
                        try {
                            localStorage.setItem(storageKey, nextTheme);
                        } catch (_) {
                            // Ignore storage failures and continue with in-memory state.
                        }
                    }

                    toggleButton.setAttribute('aria-pressed', nextTheme === 'dark' ? 'true' : 'false');
                    toggleLabel.textContent = nextTheme === 'dark' ? 'Light mode' : 'Dark mode';
                };

                setTheme(root.classList.contains('dark') ? 'dark' : 'light', false);

                toggleButton.addEventListener('click', function () {
                    setTheme(root.classList.contains('dark') ? 'light' : 'dark');
                });
            })();
        </script>
    </body>
</html>
