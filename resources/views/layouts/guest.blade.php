<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $tenant = app()->bound('tenant') ? tenant() : null;
        $branding = $tenant?->settings['branding'] ?? [];
        $brandPrimary = $branding['primary'] ?? '#0f766e';
        $brandAccent = $branding['accent'] ?? '#f59e0b';
        $brandBackground = $branding['background'] ?? '#f3f4f6';
        $logoPath = $branding['logo_path'] ?? null;
    @endphp
    <body class="font-sans text-gray-900 antialiased" style="--brand-primary: {{ $brandPrimary }}; --brand-accent: {{ $brandAccent }}; --brand-background: {{ $brandBackground }};">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 brand-background">
            <div class="flex flex-col items-center">
                @if ($logoPath && $tenant)
                    <img src="{{ route('tenant.files.show', ['path' => $logoPath]) }}" alt="Tenant logo" class="h-20 w-20 object-contain" />
                @else
                    <a href="{{ route('home') }}">
                        <x-application-logo class="w-20 h-20 fill-current text-[color:var(--brand-primary)]" />
                    </a>
                @endif
                @if ($tenant)
                    <div class="mt-3 text-sm font-semibold text-slate-700">{{ $tenant->name }}</div>
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Coffee shop</div>
                @endif
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
