<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Dashboard - BrewCloud</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-indigo-600">BrewCloud Owner</p>
                <h1 class="text-xl font-semibold">Super Admin Dashboard</h1>
            </div>
            <form method="POST" action="{{ route('super-admin.logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl space-y-8 px-6 py-8">
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Total Tenants</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['tenants']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Tenant Users</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['tenant_users']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Super Admin Accounts</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['super_admins']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Total Orders</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['orders']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Catalog Products</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['products']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Gross Sales</div>
                <div class="mt-2 text-3xl font-semibold">₱{{ number_format((float) $stats['sales_total'], 2) }}</div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Recent Tenants</h2>
                <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:text-indigo-500">View landing page</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Subdomain</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($recentTenants as $tenant)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $tenant->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->subdomain }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst((string) $tenant->plan) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->created_at?->format('M j, Y g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
