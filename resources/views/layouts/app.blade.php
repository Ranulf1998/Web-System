<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <body class="font-sans antialiased" style="font-family: 'Poppins', sans-serif; --brand-primary: {{ $brandPrimary }}; --brand-accent: {{ $brandAccent }}; --brand-background: {{ $brandBackground }};">
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

                <div class="sm:flex {{ $navPosition === 'right' ? 'sm:flex-row-reverse' : '' }}">
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
    </body>
</html>
