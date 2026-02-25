<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shop Suspended</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 flex items-center justify-center px-6">
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold">Shop Temporarily Suspended</h1>
        <p class="mt-3 text-sm text-slate-600">
            Access to <span class="font-medium">{{ $tenant->name }}</span> is currently suspended by BrewCloud central admin.
        </p>

        @if (!empty($reason))
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <span class="font-semibold">Reason:</span> {{ $reason }}
            </div>
        @endif

        @if (!empty($suspended_at))
            <p class="mt-4 text-xs text-slate-500">Suspended at: {{ \Illuminate\Support\Carbon::parse($suspended_at)->format('M j, Y g:i A') }}</p>
        @endif

        <p class="mt-6 text-sm text-slate-600">
            Contact BrewCloud support to request reactivation.
        </p>
    </div>
</body>
</html>
