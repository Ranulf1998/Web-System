<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shop Registration Status</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 flex items-center justify-center px-6">
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        @if (($status ?? 'pending') === 'declined')
            <h1 class="text-2xl font-semibold">Shop Registration Declined</h1>
            <p class="mt-3 text-sm text-slate-600">
                Registration for <span class="font-medium">{{ $tenant->name }}</span> was declined by BrewCloud super admin.
            </p>

            @if (!empty($reason))
                <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <span class="font-semibold">Reason:</span> {{ $reason }}
                </div>
            @endif

            @if (!empty($reviewed_at))
                <p class="mt-4 text-xs text-slate-500">Reviewed at: {{ \Illuminate\Support\Carbon::parse($reviewed_at)->format('M j, Y g:i A') }}</p>
            @endif
        @else
            <h1 class="text-2xl font-semibold">Shop Registration Pending Approval</h1>
            <p class="mt-3 text-sm text-slate-600">
                <span class="font-medium">{{ $tenant->name }}</span> is waiting for BrewCloud super admin approval.
            </p>

            @if (!empty($requested_at))
                <p class="mt-4 text-xs text-slate-500">Submitted at: {{ \Illuminate\Support\Carbon::parse($requested_at)->format('M j, Y g:i A') }}</p>
            @endif
        @endif

        <p class="mt-6 text-sm text-slate-600">
            You can try again later or contact BrewCloud support for an update.
        </p>
    </div>
</body>
</html>
