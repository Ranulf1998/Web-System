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
        $logoPath = $branding['logo_path'] ?? null;
        $isTenantRegistration = request()->routeIs('tenant.register*');
        $isSubdomainLogin = request()->routeIs('tenant.login');
        $guestCardWidthClass = $isTenantRegistration ? 'sm:max-w-2xl' : 'sm:max-w-md';
    @endphp
    <body class="font-sans text-gray-900 antialiased" style="font-family: 'Poppins', sans-serif; --brand-primary: {{ $brandPrimary }}; --brand-accent: {{ $brandAccent }}; --brand-background: {{ $brandBackground }};">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8 sm:py-10 brand-background">
            <div class="flex flex-col items-center">
                @if ($logoPath && $tenant)
                    <img src="{{ route('tenant.files.show', ['path' => $logoPath]) }}" alt="Tenant logo" class="h-20 w-20 object-contain" />
                @else
                    <a href="{{ route('home') }}">
                        <x-application-logo class="w-20 h-20 fill-current text-[color:var(--brand-primary)]" />
                    </a>
                @endif
                @unless ($isSubdomainLogin)
                    <div class="mt-3 text-sm font-semibold text-slate-700">BrewCloud</div>
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Coffee SaaS Platform</div>
                @endunless
                @if ($tenant)
                    <div class="mt-3 text-sm font-semibold text-slate-700">{{ $tenant->name }}</div>
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Coffee shop</div>
                @endif
            </div>

            <div class="w-full {{ $guestCardWidthClass }} mt-6 border border-slate-200 bg-white/95 px-6 py-5 shadow-xl overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
