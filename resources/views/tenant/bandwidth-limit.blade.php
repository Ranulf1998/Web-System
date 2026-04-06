<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bandwidth Limit Reached</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-6 py-10">
        <section class="w-full rounded-xl border border-rose-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold text-rose-700">Monthly Bandwidth Limit Reached</h1>
            <p class="mt-3 text-sm text-slate-700">
                Your tenant has exceeded the monthly bandwidth allocation for {{ $monthKey }}.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Current Usage</div>
                    <div class="mt-1 text-lg font-semibold text-slate-800">{{ $usageLabel }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Plan Limit</div>
                    <div class="mt-1 text-lg font-semibold text-slate-800">{{ $limitLabel }}</div>
                </div>
            </div>

            <p class="mt-6 text-sm text-slate-600">
                Please upgrade your subscription plan or contact support to restore full tenant access.
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('support-tickets.create', ['subdomain' => $tenant->subdomain]) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Contact Support
                </a>
                <form method="POST" action="{{ route('tenant.logout', ['subdomain' => $tenant->subdomain]) }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Log out</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
