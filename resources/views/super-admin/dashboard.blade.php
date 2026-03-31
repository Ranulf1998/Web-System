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
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-indigo-600">BrewCloud Owner</p>
                <h1 class="text-xl font-semibold">Super Admin Dashboard</h1>
            </div>
            <form method="POST" action="{{ route('super-admin.logout') }}" data-super-admin-logout-form>
                @csrf
                <button type="button" data-open-super-admin-logout-modal class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Logout
                </button>
            </form>
        </div>
    </header>

    @php
        $superAdminAlertType = null;
        $superAdminAlertMessage = null;

        if (session('status')) {
            $superAdminAlertType = 'success';
            $superAdminAlertMessage = session('status');
        } elseif ($errors->has('central_admin')) {
            $superAdminAlertType = 'error';
            $superAdminAlertMessage = $errors->first('central_admin');
        } elseif ($errors->has('tenant_approval')) {
            $superAdminAlertType = 'error';
            $superAdminAlertMessage = $errors->first('tenant_approval');
        }
    @endphp

    <main class="mx-auto w-full max-w-7xl space-y-8 px-6 py-8">

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Total Tenants</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['tenants']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Pending Registrations</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['pending_registrations']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Active Subscriptions</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['active_subscriptions']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Inactive Subscriptions</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['inactive_subscriptions']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Expiring in 7 Days</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['expiring_soon']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Subscription Sales</div>
                <div class="mt-2 text-3xl font-semibold">₱{{ number_format((float) $stats['sales_total'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Tenant Users</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['tenant_users']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Total DB Storage</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format(((int) $stats['total_database_bytes']) / 1024 / 1024, 2) }} MB</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm text-slate-500">Super Admin Accounts</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['super_admins']) }}</div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Tenant Management</h2>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Live</span>
                </div>
                <p class="text-sm text-slate-600">List tenants, inspect lease/storage status, and manage suspension state.</p>
                <p class="mt-3 text-sm text-slate-500">Current tenants: {{ number_format($stats['tenants']) }}</p>
                <button type="button" data-open-tenant-management class="mt-3 rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-500">
                    View Tenant Management
                </button>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Subscription Management</h2>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Live</span>
                </div>
                <p class="text-sm text-slate-600">Plan-based lease tracking and active subscription counts are now visible.</p>
                <p class="mt-3 text-sm text-slate-500">Active subscriptions: {{ number_format($stats['active_subscriptions']) }}</p>
                <p class="mt-1 text-sm text-slate-500">Inactive subscriptions: {{ number_format($stats['inactive_subscriptions']) }}</p>
                <button type="button" data-open-subscription-management class="mt-3 rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-500">
                    View Subscription Management
                </button>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Reports & Analytics</h2>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Live</span>
                </div>
                <p class="text-sm text-slate-600">Platform overview supports revenue tracking now; churn/MRR trends can be charted next.</p>
                <button type="button" data-open-reports-analytics class="mt-3 rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-500">
                    View Reports & Analytics
                </button>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-semibold">User Management</h2>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Live</span>
                </div>
                <p class="text-sm text-slate-600">Manage central admin users and assign platform roles</p>
                <p class="mt-3 text-sm text-slate-500">Tenant users: {{ number_format($stats['tenant_users']) }}</p>
                <button type="button" data-open-user-management class="mt-3 rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-500">
                    View User Management
                </button>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 md:col-span-2 xl:col-span-3">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Support / Tickets</h2>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Live</span>
                </div>
                <p class="text-sm text-slate-600">Receive and manage support tickets submitted by subdomain owners.</p>
                <p class="mt-3 text-sm text-slate-500">Total tickets: {{ number_format($stats['support_tickets']) }}</p>
                <button type="button" data-open-support-tickets class="mt-3 rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-500">
                    View Support Tickets
                </button>
            </div>
        </section>

        @php
            $pendingRegistrations = $currentTenants->filter(function ($tenant) {
                return strtolower((string) ($tenant->display_registration_status ?? 'approved')) === 'pending';
            });
        @endphp

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Pending Registrations</h2>
                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">
                    {{ number_format($pendingRegistrations->count()) }} Pending
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Subdomain</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tenant Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($pendingRegistrations as $tenant)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $tenant->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->subdomain }}.{{ config('app.domain') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->display_tenant_email }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst((string) $tenant->plan) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @php
                                        $requestedAt = $tenant->display_requested_at;
                                    @endphp
                                    @if (is_string($requestedAt) && trim($requestedAt) !== '')
                                        {{ \Illuminate\Support\Carbon::parse($requestedAt)->format('M j, Y g:i A') }}
                                    @else
                                        {{ $tenant->created_at?->format('M j, Y g:i A') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            data-open-registration-action-modal
                                            data-registration-action-type="approve"
                                            data-registration-action-url="{{ route('super-admin.tenants.approve', $tenant) }}"
                                            data-registration-tenant-name="{{ $tenant->name }}"
                                            class="rounded-md border border-emerald-300 px-2 py-1 text-xs text-emerald-700 hover:bg-emerald-50"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            data-open-registration-action-modal
                                            data-registration-action-type="decline"
                                            data-registration-action-url="{{ route('super-admin.tenants.decline', $tenant) }}"
                                            data-registration-tenant-name="{{ $tenant->name }}"
                                            class="rounded-md border border-amber-300 px-2 py-1 text-xs text-amber-700 hover:bg-amber-50"
                                        >
                                            Decline
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No pending registrations.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Tenant Management - Current Tenants</h2>
                <button type="button" data-open-tenant-domains class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-500">
                    View Tenant Domains
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Subdomain</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Tenant Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Address</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Subdomain Owner</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Lease Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Storage Use / DB</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($currentTenants as $tenant)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">
                                    {{ $tenant->name }}
                                    @if ($tenant->display_is_suspended)
                                        <div class="mt-1">
                                            <span class="inline-flex rounded-full bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700">Suspended</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <a
                                        href="{{ route('tenant.login', ['subdomain' => $tenant->subdomain]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-indigo-600 hover:text-indigo-500 hover:underline"
                                    >
                                        {{ $tenant->subdomain }}.{{ config('app.domain') }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->display_tenant_email }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->display_tenant_address }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->display_owner_name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst((string) $tenant->plan) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if ($tenant->display_lease_starts_at && $tenant->display_lease_ends_at)
                                        @php
                                            $daysLeft = now()->startOfDay()->diffInDays($tenant->display_lease_ends_at->copy()->startOfDay(), false);
                                        @endphp
                                        {{ $tenant->display_lease_starts_at->format('M j, Y') }} - {{ $tenant->display_lease_ends_at->format('M j, Y') }}
                                        <div class="text-xs text-slate-400 mt-1">
                                            {{ $tenant->display_subscription_months }} {{ $tenant->display_subscription_months === 1 ? 'month' : 'months' }} subscription
                                        </div>
                                        <div class="text-xs mt-1 {{ $daysLeft >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            @if ($daysLeft >= 0)
                                                {{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }} left
                                            @else
                                                Expired {{ abs($daysLeft) }} {{ abs($daysLeft) === 1 ? 'day' : 'days' }} ago
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $daysLeft >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $daysLeft >= 0 ? 'Active' : 'Expired' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-slate-400">Not set</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if (is_int($tenant->database_bytes))
                                        {{ number_format($tenant->database_bytes / 1024 / 1024, 2) }} MB
                                        <div class="text-xs text-slate-400">{{ $tenant->database_name ?? 'N/A' }}</div>
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $tenant->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            data-open-tenant-management
                                            data-tenant-id="{{ $tenant->id }}"
                                            data-tenant-name="{{ $tenant->name }}"
                                            data-tenant-subdomain="{{ $tenant->subdomain }}"
                                            data-tenant-plan="{{ ucfirst((string) $tenant->plan) }}"
                                            data-tenant-lease="{{ $tenant->display_lease_starts_at?->format('M j, Y') }} - {{ $tenant->display_lease_ends_at?->format('M j, Y') }}"
                                            data-tenant-months="{{ $tenant->display_subscription_months }}"
                                            data-tenant-storage="{{ is_int($tenant->database_bytes) ? number_format($tenant->database_bytes / 1024 / 1024, 2) . ' MB' : 'N/A' }}"
                                            data-tenant-db="{{ $tenant->database_name ?? 'N/A' }}"
                                            data-tenant-status="{{ $tenant->display_is_suspended ? 'Suspended' : 'Active' }}"
                                            class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"
                                        >
                                            View Details
                                        </button>

                                        @if ($tenant->display_is_suspended)
                                            <form method="POST" action="{{ route('super-admin.tenants.unsuspend', $tenant) }}" onsubmit="return confirm('Unsuspend this tenant?');">
                                                @csrf
                                                <button type="submit" class="rounded-md border border-emerald-300 px-2 py-1 text-xs text-emerald-700 hover:bg-emerald-50">Unsuspend</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspend this tenant?');">
                                                @csrf
                                                <button type="submit" class="rounded-md border border-rose-300 px-2 py-1 text-xs text-rose-700 hover:bg-rose-50">Suspend</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-sm text-slate-500">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <dialog id="super-admin-logout-confirm-modal" class="w-full max-w-md rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <h3 class="text-base font-semibold text-slate-900">Confirm Logout</h3>
            <p class="mt-2 text-sm text-slate-600">Are you sure you want to log out?</p>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-super-admin-logout-cancel class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" data-super-admin-logout-confirm class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">Log Out</button>
            </div>
        </div>
    </dialog>

    <dialog id="registration-action-confirm-modal" class="w-full max-w-md rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <h3 class="text-base font-semibold text-slate-900" data-registration-modal-title>Confirm Action</h3>
            <p class="mt-2 text-sm text-slate-600" data-registration-modal-message>Are you sure you want to continue?</p>

            <div class="mt-4 hidden" data-registration-modal-reason-wrap>
                <label for="registration-modal-reason" class="text-sm font-medium text-slate-700">Decline reason <span class="text-rose-600">*</span></label>
                <textarea
                    id="registration-modal-reason"
                    name="reason"
                    rows="3"
                    maxlength="255"
                    disabled
                    class="mt-1 w-full rounded-md border border-slate-300 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    placeholder="Enter the reason for declining this registration"
                ></textarea>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-registration-modal-cancel class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                <form method="POST" data-registration-modal-form>
                    @csrf
                    <button type="submit" data-registration-modal-confirm class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">Confirm</button>
                </form>
            </div>
        </div>
    </dialog>

    @if ($superAdminAlertMessage)
        <dialog id="super-admin-message-alert-modal" class="w-full max-w-md rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
            <div class="rounded-xl bg-white p-6">
                <h3 class="text-base font-semibold text-slate-900">
                    {{ $superAdminAlertType === 'success' ? 'Success' : 'Alert' }}
                </h3>
                <p class="mt-2 text-sm {{ $superAdminAlertType === 'success' ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $superAdminAlertMessage }}
                </p>

                <div class="mt-5 flex items-center justify-end">
                    <button type="button" data-super-admin-message-alert-close class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">OK</button>
                </div>
            </div>
        </dialog>
    @endif

    <dialog id="tenant-domains-modal" class="w-full max-w-2xl rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Tenant Domains</h2>
                <button type="button" data-close-tenant-domains class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>

            @php
                $baseDomain = (string) config('app.domain');
            @endphp

            <div class="max-h-80 overflow-y-auto rounded-lg border border-slate-200">
                <ul class="divide-y divide-slate-200">
                    @forelse ($currentTenants as $tenantDomainItem)
                        <li class="px-4 py-3 text-sm text-slate-700">
                            <a
                                href="{{ route('tenant.login', ['subdomain' => $tenantDomainItem->subdomain]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-indigo-600 hover:text-indigo-500 hover:underline"
                            >
                                {{ $tenantDomainItem->subdomain }}.{{ $baseDomain }}
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-3 text-sm text-slate-500">No tenant domains found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </dialog>

    <dialog id="tenant-management-modal" class="w-full max-w-5xl rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Tenant Management</h2>
                <button type="button" data-close-tenant-management class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Tenant Name</div>
                    <div class="mt-1 text-sm font-medium text-slate-800" data-tenant-detail="name">Select a tenant</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Subdomain</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="subdomain">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Tenant Email</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="email">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Address</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="address">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Subdomain Owner</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="owner">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Plan</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="plan">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Lease Time</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="lease">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Subscription</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="months">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Storage / DB</div>
                    <div class="mt-1 text-sm text-slate-700" data-tenant-detail="storage">-</div>
                    <div class="mt-1 text-xs text-slate-500" data-tenant-detail="db">-</div>

                    <div class="mt-3 text-xs uppercase tracking-wide text-slate-500">Status</div>
                    <div class="mt-1 text-sm font-medium text-slate-700" data-tenant-detail="status">-</div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-800">Suspend Tenant</h3>
                    <p class="mt-1 text-xs text-slate-500">Use this to temporarily block tenant access.</p>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Choose Tenant to View</label>
                        <select id="tenant-management-selector" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select a tenant</option>
                            @foreach ($currentTenants as $tenant)
                                @php
                                    $registrationStatus = strtolower((string) ($tenant->display_registration_status ?? 'approved'));
                                    $statusLabel = $registrationStatus === 'pending'
                                        ? 'Pending Approval'
                                        : ($registrationStatus === 'declined' ? 'Declined' : ($tenant->display_is_suspended ? 'Suspended' : 'Active'));
                                @endphp
                                <option
                                    value="{{ $tenant->id }}"
                                    data-tenant-id="{{ $tenant->id }}"
                                    data-tenant-name="{{ $tenant->name }}"
                                    data-tenant-subdomain="{{ $tenant->subdomain }}"
                                    data-tenant-email="{{ $tenant->display_tenant_email }}"
                                    data-tenant-address="{{ $tenant->display_tenant_address }}"
                                    data-tenant-owner="{{ $tenant->display_owner_name }}"
                                    data-tenant-plan="{{ ucfirst((string) $tenant->plan) }}"
                                    data-tenant-lease="{{ $tenant->display_lease_starts_at?->format('M j, Y') }} - {{ $tenant->display_lease_ends_at?->format('M j, Y') }}"
                                    data-tenant-months="{{ $tenant->display_subscription_months }}"
                                    data-tenant-storage="{{ is_int($tenant->database_bytes) ? number_format($tenant->database_bytes / 1024 / 1024, 2) . ' MB' : 'N/A' }}"
                                    data-tenant-db="{{ $tenant->database_name ?? 'N/A' }}"
                                    data-tenant-status="{{ $statusLabel }}"
                                >
                                    {{ $tenant->name }} ({{ $tenant->subdomain }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <h3 class="text-sm font-semibold text-slate-800">Registration Review</h3>
                        <p class="mt-1 text-xs text-slate-500">Approve or decline tenant registration submissions.</p>

                        <form method="POST" action="{{ route('super-admin.tenants.approve', ['tenant' => '__TENANT_ID__']) }}" data-tenant-approve-form class="mt-3">
                            @csrf
                            <button type="submit" data-tenant-approve-submit disabled class="rounded-md border border-emerald-300 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">Approve Selected Tenant</button>
                        </form>

                        <form method="POST" action="{{ route('super-admin.tenants.decline', ['tenant' => '__TENANT_ID__']) }}" data-tenant-decline-form class="mt-3">
                            @csrf
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Decline Reason (optional)</label>
                            <input name="reason" type="text" maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Suspicious or incomplete registration details.">
                            <button type="submit" data-tenant-decline-submit disabled class="mt-3 rounded-md border border-amber-300 px-3 py-2 text-xs font-medium text-amber-700 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50">Decline Selected Tenant</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('super-admin.tenants.suspend', ['tenant' => '__TENANT_ID__']) }}" data-tenant-suspend-form class="mt-3">
                        @csrf
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Reason (optional)</label>
                        <input name="reason" type="text" maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Policy review, billing issue, etc.">
                        <button type="submit" data-tenant-suspend-submit disabled class="mt-3 rounded-md border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50">Suspend Selected Tenant</button>
                    </form>

                    <form method="POST" action="{{ route('super-admin.tenants.unsuspend', ['tenant' => '__TENANT_ID__']) }}" data-tenant-unsuspend-form class="mt-3">
                        @csrf
                        <button type="submit" data-tenant-unsuspend-submit disabled class="rounded-md border border-emerald-300 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">Unsuspend Selected Tenant</button>
                    </form>
                </div>
            </div>

            <div class="mt-4 text-xs text-slate-500">Tip: click any "View Details" button in the tenant table to load tenant info here.</div>
        </div>
    </dialog>

    <dialog id="subscription-management-modal" class="w-full max-w-5xl rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Subscription Management</h2>
                <button type="button" data-close-subscription-management class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Choose Tenant</label>
                    <select id="subscription-tenant-selector" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select a tenant</option>
                        @foreach ($currentTenants as $tenant)
                            <option
                                value="{{ $tenant->id }}"
                                data-tenant-id="{{ $tenant->id }}"
                                data-tenant-name="{{ $tenant->name }}"
                                data-tenant-email="{{ $tenant->display_tenant_email }}"
                                data-tenant-address="{{ $tenant->display_tenant_address }}"
                                data-tenant-owner="{{ $tenant->display_owner_name }}"
                                data-tenant-plan-key="{{ $tenant->planKey() }}"
                                data-tenant-plan="{{ ucfirst((string) $tenant->plan) }}"
                                data-tenant-subdomain="{{ $tenant->subdomain }}"
                                data-tenant-lease-end="{{ $tenant->display_lease_ends_at?->format('M j, Y') ?? 'Not set' }}"
                                data-tenant-months="{{ $tenant->display_subscription_months }}"
                                data-tenant-payment-method="{{ strtoupper((string) $tenant->display_payment_method) }}"
                                data-tenant-monthly-price="{{ number_format((float) $tenant->display_monthly_price, 2) }}"
                                data-tenant-renewal-history='@json($tenant->display_renewal_history)'
                            >
                                {{ $tenant->name }} ({{ $tenant->subdomain }})
                            </option>
                        @endforeach
                    </select>

                    <div class="mt-4 space-y-2 text-sm text-slate-700">
                        <div><span class="font-medium">Name:</span> <span data-subscription-detail="name">-</span></div>
                        <div><span class="font-medium">Tenant Email:</span> <span data-subscription-detail="email">-</span></div>
                        <div><span class="font-medium">Address:</span> <span data-subscription-detail="address">-</span></div>
                        <div><span class="font-medium">Subdomain Owner:</span> <span data-subscription-detail="owner">-</span></div>
                        <div><span class="font-medium">Plan:</span> <span data-subscription-detail="plan">-</span></div>
                        <div><span class="font-medium">Current Lease End:</span> <span data-subscription-detail="lease_end">-</span></div>
                        <div><span class="font-medium">Subscribed Months:</span> <span data-subscription-detail="months">-</span></div>
                        <div><span class="font-medium">Current Payment Method:</span> <span data-subscription-detail="payment_method">-</span></div>
                        <div><span class="font-medium">Monthly Price:</span> ₱<span data-subscription-detail="monthly_price">0.00</span></div>
                    </div>

                    <div class="mt-4 rounded-md border border-slate-200 bg-white p-3">
                        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Renewal History</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-500">
                                        <th class="py-1 text-left font-medium">Last Paid Date</th>
                                        <th class="py-1 text-left font-medium">Method</th>
                                        <th class="py-1 text-left font-medium">Amount</th>
                                    </tr>
                                </thead>
                                <tbody data-subscription-renewal-history>
                                    <tr>
                                        <td colspan="3" class="py-2 text-slate-400">No renewal records yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-800">Renew Subscription</h3>
                    <p class="mt-1 text-xs text-slate-500">Extend tenant subscription by adding months.</p>

                    <form method="POST" action="{{ route('super-admin.tenants.subscription.renew', ['tenant' => '__TENANT_ID__']) }}" data-subscription-renew-form class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Add Months</label>
                            <select name="add_months" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                @for ($month = 1; $month <= 24; $month++)
                                    <option value="{{ $month }}">{{ $month }} {{ $month === 1 ? 'month' : 'months' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Payment Method</label>
                            <select name="payment_method" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option value="gcash">GCash</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <button type="submit" data-subscription-renew-submit disabled class="rounded-md border border-indigo-300 px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50">Renew Selected Tenant</button>
                    </form>

                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <h3 class="text-sm font-semibold text-slate-800">Change Plan</h3>
                        <p class="mt-1 text-xs text-slate-500">Switch the selected tenant to a different subscription plan.</p>

                        <form method="POST" action="{{ route('super-admin.tenants.subscription.plan.update', ['tenant' => '__TENANT_ID__']) }}" data-subscription-plan-form class="mt-3 space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Plan</label>
                                <select name="plan" data-subscription-plan-selector class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    @foreach (config('plans', []) as $planKey => $planConfig)
                                        <option value="{{ $planKey }}">{{ data_get($planConfig, 'name', ucfirst((string) $planKey)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" data-subscription-plan-submit disabled class="rounded-md border border-amber-300 px-3 py-2 text-xs font-medium text-amber-700 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50">Change Plan for Selected Tenant</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <dialog id="reports-analytics-modal" class="w-full max-w-6xl rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Reports & Analytics</h2>
                <button type="button" data-close-reports-analytics class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Subscription Sales</div>
                    <div class="mt-1 text-lg font-semibold text-slate-800">₱{{ number_format((float) $stats['sales_total'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Active Subscriptions</div>
                    <div class="mt-1 text-lg font-semibold text-slate-800">{{ number_format((int) $stats['active_subscriptions']) }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Inactive Subscriptions</div>
                    <div class="mt-1 text-lg font-semibold text-slate-800">{{ number_format((int) $stats['inactive_subscriptions']) }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Expiring in 7 Days</div>
                    <div class="mt-1 text-lg font-semibold text-slate-800">{{ number_format((int) $stats['expiring_soon']) }}</div>
                </div>
            </section>

            <section class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-800">MRR by Plan</h3>
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-2">Plan</th>
                                    <th class="py-2 pr-2">Tenants</th>
                                    <th class="py-2">MRR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mrrByPlan as $row)
                                    <tr class="border-b border-slate-100 text-slate-700">
                                        <td class="py-2 pr-2">{{ $row['plan'] }}</td>
                                        <td class="py-2 pr-2">{{ number_format((int) $row['tenants']) }}</td>
                                        <td class="py-2">₱{{ number_format((float) $row['mrr'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-3 text-slate-400">No active subscription data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-800">Recent Subscription Payments</h3>
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-2">Tenant</th>
                                    <th class="py-2 pr-2">Date</th>
                                    <th class="py-2 pr-2">Method</th>
                                    <th class="py-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSubscriptionPayments as $payment)
                                    <tr class="border-b border-slate-100 text-slate-700">
                                        <td class="py-2 pr-2">{{ $payment['tenant_name'] }}</td>
                                        <td class="py-2 pr-2">{{ $payment['paid_at']?->format('M j, Y g:i A') ?? 'N/A' }}</td>
                                        <td class="py-2 pr-2">{{ $payment['payment_method'] }}</td>
                                        <td class="py-2">₱{{ number_format((float) $payment['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-slate-400">No renewal payments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="mt-4 rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold text-slate-800">Nearest Subscription Expirations</h3>
                <div class="mt-2 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-2">Tenant</th>
                                <th class="py-2 pr-2">Subdomain</th>
                                <th class="py-2 pr-2">Plan</th>
                                <th class="py-2 pr-2">Lease End</th>
                                <th class="py-2">Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expiringSubscriptionRows as $row)
                                <tr class="border-b border-slate-100 text-slate-700">
                                    <td class="py-2 pr-2">{{ $row['tenant_name'] }}</td>
                                    <td class="py-2 pr-2">{{ $row['subdomain'] }}</td>
                                    <td class="py-2 pr-2">{{ $row['plan'] }}</td>
                                    <td class="py-2 pr-2">{{ $row['lease_end']?->format('M j, Y') ?? 'N/A' }}</td>
                                    <td class="py-2">{{ is_int($row['days_left']) ? $row['days_left'] : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-3 text-slate-400">No active subscriptions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </dialog>

    <dialog id="support-tickets-modal" class="w-full max-w-6xl rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Support Tickets</h2>
                <button type="button" data-close-support-tickets class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Shop</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($supportTickets as $ticket)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-medium">{{ $ticket->shop_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $ticket->subdomain }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->subject }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 max-w-sm">{{ $ticket->message }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $ticket->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <form method="POST" action="{{ route('super-admin.support-tickets.status.update', $ticket) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="rounded-md border border-slate-300 px-2 py-1 text-xs">
                                            <option value="open" @selected($ticket->status === 'open')>Open</option>
                                            <option value="in_progress" @selected($ticket->status === 'in_progress')>In Progress</option>
                                            <option value="resolved" @selected($ticket->status === 'resolved')>Resolved</option>
                                        </select>
                                        <input
                                            type="text"
                                            name="resolution_note"
                                            value="{{ $ticket->resolution_note }}"
                                            maxlength="1000"
                                            placeholder="Resolution note (optional)"
                                            class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs"
                                        >
                                        <button type="submit" class="rounded-md border border-indigo-300 px-2 py-1 text-xs text-indigo-700 hover:bg-indigo-50">Update</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No support tickets yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>

    <dialog id="user-management-modal" class="w-full max-w-6xl rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">User Management - Central Admin Controls</h2>
                <button type="button" data-close-user-management class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Close</button>
            </div>

            <form method="POST" action="{{ route('super-admin.central-admins.store') }}" class="mb-6 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 lg:grid-cols-5">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
                    <input name="name" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Admin name">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Email</label>
                    <input name="email" type="email" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="admin@brewcloud.test">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Password</label>
                    <input name="password" type="password" required minlength="8" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Minimum 8 characters">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Confirm Password</label>
                    <input name="password_confirmation" type="password" required minlength="8" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Repeat password">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Role</label>
                    <select name="role" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($centralRoleOptions as $roleOption)
                            <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 lg:col-span-5">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Create Central Admin</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Current Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Role Control</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($centralAdmins as $admin)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $admin->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $admin->email }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $admin->roles->first()?->name ?? 'No role' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <form method="POST" action="{{ route('super-admin.central-admins.role.update', $admin) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="rounded-md border border-slate-300 px-2 py-1 text-xs">
                                            @foreach ($centralRoleOptions as $roleOption)
                                                <option value="{{ $roleOption }}" @selected(($admin->roles->first()?->name) === $roleOption)>{{ $roleOption }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50">Update</button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if ((int) auth()->id() === (int) $admin->id)
                                        <span class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-500">Current User</span>
                                    @else
                                        <form method="POST" action="{{ route('super-admin.central-admins.destroy', $admin) }}" onsubmit="return confirm('Remove this central admin account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-rose-300 px-2 py-1 text-xs text-rose-700 hover:bg-rose-50">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No central admin accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>

    <script>
        (function () {
            const supportModal = document.getElementById('support-tickets-modal');
            const supportOpenButton = document.querySelector('[data-open-support-tickets]');
            const supportCloseButton = document.querySelector('[data-close-support-tickets]');

            if (supportModal && supportOpenButton && supportCloseButton) {
                supportOpenButton.addEventListener('click', function () {
                    supportModal.showModal();
                });

                supportCloseButton.addEventListener('click', function () {
                    supportModal.close();
                });

                supportModal.addEventListener('click', function (event) {
                    const rect = supportModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        supportModal.close();
                    }
                });
            }

            const reportsModal = document.getElementById('reports-analytics-modal');
            const reportsOpenButton = document.querySelector('[data-open-reports-analytics]');
            const reportsCloseButton = document.querySelector('[data-close-reports-analytics]');

            if (reportsModal && reportsOpenButton && reportsCloseButton) {
                reportsOpenButton.addEventListener('click', function () {
                    reportsModal.showModal();
                });

                reportsCloseButton.addEventListener('click', function () {
                    reportsModal.close();
                });

                reportsModal.addEventListener('click', function (event) {
                    const rect = reportsModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        reportsModal.close();
                    }
                });
            }

            const subscriptionModal = document.getElementById('subscription-management-modal');
            const subscriptionOpenButton = document.querySelector('[data-open-subscription-management]');
            const subscriptionCloseButton = document.querySelector('[data-close-subscription-management]');
            const subscriptionTenantSelector = document.getElementById('subscription-tenant-selector');
            const subscriptionRenewForm = document.querySelector('[data-subscription-renew-form]');
            const subscriptionRenewSubmit = document.querySelector('[data-subscription-renew-submit]');
            const subscriptionPlanForm = document.querySelector('[data-subscription-plan-form]');
            const subscriptionPlanSubmit = document.querySelector('[data-subscription-plan-submit]');
            const subscriptionPlanSelector = document.querySelector('[data-subscription-plan-selector]');
            const subscriptionHistoryBody = document.querySelector('[data-subscription-renewal-history]');
            const subscriptionDetails = {
                name: document.querySelector('[data-subscription-detail="name"]'),
                email: document.querySelector('[data-subscription-detail="email"]'),
                address: document.querySelector('[data-subscription-detail="address"]'),
                owner: document.querySelector('[data-subscription-detail="owner"]'),
                plan: document.querySelector('[data-subscription-detail="plan"]'),
                lease_end: document.querySelector('[data-subscription-detail="lease_end"]'),
                months: document.querySelector('[data-subscription-detail="months"]'),
                payment_method: document.querySelector('[data-subscription-detail="payment_method"]'),
                monthly_price: document.querySelector('[data-subscription-detail="monthly_price"]'),
            };

            const renderSubscriptionHistory = (items) => {
                if (!subscriptionHistoryBody) {
                    return;
                }

                if (!Array.isArray(items) || !items.length) {
                    subscriptionHistoryBody.innerHTML = '<tr><td colspan="3" class="py-2 text-slate-400">No renewal records yet.</td></tr>';
                    return;
                }

                const rows = items.slice(0, 10).map((entry) => {
                    const paidAt = entry && entry.paid_at
                        ? new Date(entry.paid_at).toLocaleString('en-PH', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit'
                        })
                        : '-';
                    const method = entry && entry.payment_method ? String(entry.payment_method).toUpperCase() : '-';
                    const amount = entry && typeof entry.amount !== 'undefined'
                        ? Number(entry.amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        : '0.00';

                    return `<tr class="border-b border-slate-100 text-slate-700"><td class="py-1 pr-2">${paidAt}</td><td class="py-1 pr-2">${method}</td><td class="py-1">₱${amount}</td></tr>`;
                });

                subscriptionHistoryBody.innerHTML = rows.join('');
            };

            const setSubscriptionState = (enabled) => {
                if (subscriptionRenewSubmit) {
                    subscriptionRenewSubmit.disabled = !enabled;
                }

                if (subscriptionPlanSubmit) {
                    subscriptionPlanSubmit.disabled = !enabled;
                }
            };

            const updateSubscriptionDetails = (source) => {
                if (!source || !source.value) {
                    if (subscriptionDetails.name) subscriptionDetails.name.textContent = '-';
                    if (subscriptionDetails.email) subscriptionDetails.email.textContent = '-';
                    if (subscriptionDetails.address) subscriptionDetails.address.textContent = '-';
                    if (subscriptionDetails.owner) subscriptionDetails.owner.textContent = '-';
                    if (subscriptionDetails.plan) subscriptionDetails.plan.textContent = '-';
                    if (subscriptionDetails.lease_end) subscriptionDetails.lease_end.textContent = '-';
                    if (subscriptionDetails.months) subscriptionDetails.months.textContent = '-';
                    if (subscriptionDetails.payment_method) subscriptionDetails.payment_method.textContent = '-';
                    if (subscriptionDetails.monthly_price) subscriptionDetails.monthly_price.textContent = '0.00';
                    renderSubscriptionHistory([]);
                    setSubscriptionState(false);
                    return;
                }

                const get = (key, fallback = '-') => source.getAttribute(`data-tenant-${key}`) || fallback;

                if (subscriptionDetails.name) subscriptionDetails.name.textContent = get('name');
                if (subscriptionDetails.email) subscriptionDetails.email.textContent = get('email');
                if (subscriptionDetails.address) subscriptionDetails.address.textContent = get('address');
                if (subscriptionDetails.owner) subscriptionDetails.owner.textContent = get('owner');
                if (subscriptionDetails.plan) subscriptionDetails.plan.textContent = get('plan');
                if (subscriptionDetails.lease_end) subscriptionDetails.lease_end.textContent = get('lease-end');
                if (subscriptionDetails.months) subscriptionDetails.months.textContent = `${get('months')} month(s)`;
                if (subscriptionDetails.payment_method) subscriptionDetails.payment_method.textContent = get('payment-method');
                if (subscriptionDetails.monthly_price) subscriptionDetails.monthly_price.textContent = get('monthly-price', '0.00');

                let history = [];
                try {
                    history = JSON.parse(source.getAttribute('data-tenant-renewal-history') || '[]');
                } catch (_) {
                    history = [];
                }
                renderSubscriptionHistory(history);

                if (subscriptionRenewForm) {
                    subscriptionRenewForm.action = `{{ url('/super-admin/tenants') }}/${source.value}/subscription/renew`;
                }

                if (subscriptionPlanForm) {
                    subscriptionPlanForm.action = `{{ url('/super-admin/tenants') }}/${source.value}/subscription/plan`;
                }

                if (subscriptionPlanSelector) {
                    subscriptionPlanSelector.value = get('plan-key', '').toLowerCase();
                }

                setSubscriptionState(true);
            };

            if (subscriptionTenantSelector) {
                subscriptionTenantSelector.addEventListener('change', function () {
                    const selected = subscriptionTenantSelector.options[subscriptionTenantSelector.selectedIndex];
                    updateSubscriptionDetails(selected);
                });
            }

            if (subscriptionModal && subscriptionOpenButton && subscriptionCloseButton) {
                subscriptionOpenButton.addEventListener('click', function () {
                    subscriptionModal.showModal();
                });

                subscriptionCloseButton.addEventListener('click', function () {
                    subscriptionModal.close();
                });

                subscriptionModal.addEventListener('click', function (event) {
                    const rect = subscriptionModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        subscriptionModal.close();
                    }
                });
            }

            const tenantModal = document.getElementById('tenant-management-modal');
            const tenantOpenButtons = document.querySelectorAll('[data-open-tenant-management]');
            const tenantCloseButton = document.querySelector('[data-close-tenant-management]');
            const tenantDetailNodes = {
                name: document.querySelector('[data-tenant-detail="name"]'),
                subdomain: document.querySelector('[data-tenant-detail="subdomain"]'),
                email: document.querySelector('[data-tenant-detail="email"]'),
                address: document.querySelector('[data-tenant-detail="address"]'),
                owner: document.querySelector('[data-tenant-detail="owner"]'),
                plan: document.querySelector('[data-tenant-detail="plan"]'),
                lease: document.querySelector('[data-tenant-detail="lease"]'),
                months: document.querySelector('[data-tenant-detail="months"]'),
                storage: document.querySelector('[data-tenant-detail="storage"]'),
                db: document.querySelector('[data-tenant-detail="db"]'),
                status: document.querySelector('[data-tenant-detail="status"]'),
            };
            const tenantSuspendForm = document.querySelector('[data-tenant-suspend-form]');
            const tenantUnsuspendForm = document.querySelector('[data-tenant-unsuspend-form]');
            const tenantSuspendSubmit = document.querySelector('[data-tenant-suspend-submit]');
            const tenantUnsuspendSubmit = document.querySelector('[data-tenant-unsuspend-submit]');
            const tenantApproveForm = document.querySelector('[data-tenant-approve-form]');
            const tenantDeclineForm = document.querySelector('[data-tenant-decline-form]');
            const tenantApproveSubmit = document.querySelector('[data-tenant-approve-submit]');
            const tenantDeclineSubmit = document.querySelector('[data-tenant-decline-submit]');
            const tenantSelector = document.getElementById('tenant-management-selector');

            const updateTenantActions = (tenantId) => {
                if (!tenantSuspendForm || !tenantUnsuspendForm || !tenantId) {
                    if (tenantSuspendSubmit) tenantSuspendSubmit.disabled = true;
                    if (tenantUnsuspendSubmit) tenantUnsuspendSubmit.disabled = true;
                    if (tenantApproveSubmit) tenantApproveSubmit.disabled = true;
                    if (tenantDeclineSubmit) tenantDeclineSubmit.disabled = true;
                    return;
                }

                tenantSuspendForm.action = `{{ url('/super-admin/tenants') }}/${tenantId}/suspend`;
                tenantUnsuspendForm.action = `{{ url('/super-admin/tenants') }}/${tenantId}/unsuspend`;
                if (tenantApproveForm) {
                    tenantApproveForm.action = `{{ url('/super-admin/tenants') }}/${tenantId}/approve`;
                }
                if (tenantDeclineForm) {
                    tenantDeclineForm.action = `{{ url('/super-admin/tenants') }}/${tenantId}/decline`;
                }
                if (tenantSuspendSubmit) tenantSuspendSubmit.disabled = false;
                if (tenantUnsuspendSubmit) tenantUnsuspendSubmit.disabled = false;
                if (tenantApproveSubmit) tenantApproveSubmit.disabled = false;
                if (tenantDeclineSubmit) tenantDeclineSubmit.disabled = false;
            };

            const fillTenantDetails = (source) => {
                if (!source) {
                    return;
                }

                const get = (key, fallback = '-') => source.getAttribute(`data-tenant-${key}`) || fallback;

                if (tenantDetailNodes.name) tenantDetailNodes.name.textContent = get('name');
                if (tenantDetailNodes.subdomain) tenantDetailNodes.subdomain.textContent = get('subdomain');
                if (tenantDetailNodes.email) tenantDetailNodes.email.textContent = get('email');
                if (tenantDetailNodes.address) tenantDetailNodes.address.textContent = get('address');
                if (tenantDetailNodes.owner) tenantDetailNodes.owner.textContent = get('owner');
                if (tenantDetailNodes.plan) tenantDetailNodes.plan.textContent = get('plan');
                if (tenantDetailNodes.lease) tenantDetailNodes.lease.textContent = get('lease');
                if (tenantDetailNodes.months) tenantDetailNodes.months.textContent = `${get('months')} month(s)`;
                if (tenantDetailNodes.storage) tenantDetailNodes.storage.textContent = get('storage');
                if (tenantDetailNodes.db) tenantDetailNodes.db.textContent = get('db');
                if (tenantDetailNodes.status) tenantDetailNodes.status.textContent = get('status');

                updateTenantActions(source.getAttribute('data-tenant-id'));
            };

            if (tenantSelector) {
                tenantSelector.addEventListener('change', function () {
                    const selected = tenantSelector.options[tenantSelector.selectedIndex];

                    if (!selected || !selected.value) {
                        updateTenantActions('');

                        if (tenantDetailNodes.name) tenantDetailNodes.name.textContent = 'Select a tenant';
                        if (tenantDetailNodes.subdomain) tenantDetailNodes.subdomain.textContent = '-';
                        if (tenantDetailNodes.email) tenantDetailNodes.email.textContent = '-';
                        if (tenantDetailNodes.address) tenantDetailNodes.address.textContent = '-';
                        if (tenantDetailNodes.owner) tenantDetailNodes.owner.textContent = '-';
                        if (tenantDetailNodes.plan) tenantDetailNodes.plan.textContent = '-';
                        if (tenantDetailNodes.lease) tenantDetailNodes.lease.textContent = '-';
                        if (tenantDetailNodes.months) tenantDetailNodes.months.textContent = '-';
                        if (tenantDetailNodes.storage) tenantDetailNodes.storage.textContent = '-';
                        if (tenantDetailNodes.db) tenantDetailNodes.db.textContent = '-';
                        if (tenantDetailNodes.status) tenantDetailNodes.status.textContent = '-';
                        return;
                    }

                    fillTenantDetails(selected);
                });
            }

            if (tenantModal && tenantOpenButtons.length && tenantCloseButton) {
                tenantOpenButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const tenantId = button.getAttribute('data-tenant-id');

                        if (tenantSelector && tenantId) {
                            tenantSelector.value = tenantId;
                            const selected = tenantSelector.options[tenantSelector.selectedIndex];
                            fillTenantDetails(selected);
                        } else if (tenantSelector) {
                            tenantSelector.value = '';
                            updateTenantActions('');
                        }

                        if (tenantId) {
                            fillTenantDetails(button);
                        }

                        tenantModal.showModal();
                    });
                });

                tenantCloseButton.addEventListener('click', function () {
                    tenantModal.close();
                });

                tenantModal.addEventListener('click', function (event) {
                    const rect = tenantModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        tenantModal.close();
                    }
                });
            }

            const tenantDomainsModal = document.getElementById('tenant-domains-modal');
            const tenantDomainsOpenButton = document.querySelector('[data-open-tenant-domains]');
            const tenantDomainsCloseButton = document.querySelector('[data-close-tenant-domains]');

            if (tenantDomainsModal && tenantDomainsOpenButton && tenantDomainsCloseButton) {
                tenantDomainsOpenButton.addEventListener('click', function () {
                    tenantDomainsModal.showModal();
                });

                tenantDomainsCloseButton.addEventListener('click', function () {
                    tenantDomainsModal.close();
                });

                tenantDomainsModal.addEventListener('click', function (event) {
                    const rect = tenantDomainsModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        tenantDomainsModal.close();
                    }
                });
            }

            const modal = document.getElementById('user-management-modal');
            const openButton = document.querySelector('[data-open-user-management]');
            const closeButton = document.querySelector('[data-close-user-management]');

            const superAdminLogoutModal = document.getElementById('super-admin-logout-confirm-modal');
            const superAdminLogoutForm = document.querySelector('[data-super-admin-logout-form]');
            const superAdminLogoutOpenButton = document.querySelector('[data-open-super-admin-logout-modal]');
            const superAdminLogoutCancelButton = document.querySelector('[data-super-admin-logout-cancel]');
            const superAdminLogoutConfirmButton = document.querySelector('[data-super-admin-logout-confirm]');
            const superAdminMessageAlertModal = document.getElementById('super-admin-message-alert-modal');
            const superAdminMessageAlertCloseButton = document.querySelector('[data-super-admin-message-alert-close]');
            const registrationActionModal = document.getElementById('registration-action-confirm-modal');
            const registrationActionOpenButtons = document.querySelectorAll('[data-open-registration-action-modal]');
            const registrationActionModalTitle = document.querySelector('[data-registration-modal-title]');
            const registrationActionModalMessage = document.querySelector('[data-registration-modal-message]');
            const registrationActionModalCancel = document.querySelector('[data-registration-modal-cancel]');
            const registrationActionModalForm = document.querySelector('[data-registration-modal-form]');
            const registrationActionModalConfirm = document.querySelector('[data-registration-modal-confirm]');
            const registrationActionModalReasonWrap = document.querySelector('[data-registration-modal-reason-wrap]');
            const registrationActionModalReasonField = document.getElementById('registration-modal-reason');

            if (registrationActionModal && registrationActionOpenButtons.length && registrationActionModalTitle && registrationActionModalMessage && registrationActionModalCancel && registrationActionModalForm && registrationActionModalConfirm && registrationActionModalReasonWrap && registrationActionModalReasonField) {
                const resetRegistrationActionModal = () => {
                    registrationActionModalReasonWrap.classList.add('hidden');
                    registrationActionModalReasonField.required = false;
                    registrationActionModalReasonField.disabled = true;
                    registrationActionModalReasonField.value = '';
                };

                registrationActionOpenButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        const actionType = button.getAttribute('data-registration-action-type');
                        const actionUrl = button.getAttribute('data-registration-action-url');
                        const tenantName = button.getAttribute('data-registration-tenant-name') || 'this tenant';

                        if (!actionUrl) {
                            return;
                        }

                        const isApprove = actionType === 'approve';

                        registrationActionModalTitle.textContent = isApprove ? 'Approve Registration' : 'Decline Registration';
                        registrationActionModalMessage.textContent = isApprove
                            ? `Approve registration for ${tenantName}?`
                            : `Decline registration for ${tenantName}?`;
                        registrationActionModalForm.action = actionUrl;
                        registrationActionModalConfirm.textContent = isApprove ? 'Approve' : 'Decline';
                        registrationActionModalConfirm.classList.toggle('bg-emerald-600', isApprove);
                        registrationActionModalConfirm.classList.toggle('hover:bg-emerald-500', isApprove);
                        registrationActionModalConfirm.classList.toggle('bg-amber-600', !isApprove);
                        registrationActionModalConfirm.classList.toggle('hover:bg-amber-500', !isApprove);
                        registrationActionModalConfirm.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');

                        registrationActionModalReasonWrap.classList.toggle('hidden', isApprove);
                        registrationActionModalReasonField.required = !isApprove;
                        registrationActionModalReasonField.disabled = isApprove;

                        if (isApprove) {
                            registrationActionModalReasonField.value = '';
                        }

                        registrationActionModal.showModal();

                        if (!isApprove) {
                            setTimeout(function () {
                                registrationActionModalReasonField.focus();
                            }, 50);
                        }
                    });
                });

                registrationActionModalCancel.addEventListener('click', function () {
                    resetRegistrationActionModal();
                    registrationActionModal.close();
                });

                registrationActionModal.addEventListener('click', function (event) {
                    const rect = registrationActionModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        resetRegistrationActionModal();
                        registrationActionModal.close();
                    }
                });

                registrationActionModal.addEventListener('close', function () {
                    resetRegistrationActionModal();
                });
            }

            if (superAdminMessageAlertModal && superAdminMessageAlertCloseButton) {
                superAdminMessageAlertModal.showModal();

                superAdminMessageAlertCloseButton.addEventListener('click', function () {
                    superAdminMessageAlertModal.close();
                });

                superAdminMessageAlertModal.addEventListener('click', function (event) {
                    const rect = superAdminMessageAlertModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        superAdminMessageAlertModal.close();
                    }
                });
            }

            if (superAdminLogoutModal && superAdminLogoutForm && superAdminLogoutOpenButton && superAdminLogoutCancelButton && superAdminLogoutConfirmButton) {
                superAdminLogoutOpenButton.addEventListener('click', function () {
                    superAdminLogoutModal.showModal();
                });

                superAdminLogoutCancelButton.addEventListener('click', function () {
                    superAdminLogoutModal.close();
                });

                superAdminLogoutConfirmButton.addEventListener('click', function () {
                    superAdminLogoutForm.submit();
                });

                superAdminLogoutModal.addEventListener('click', function (event) {
                    const rect = superAdminLogoutModal.getBoundingClientRect();
                    const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                    if (!inDialog) {
                        superAdminLogoutModal.close();
                    }
                });
            }

            if (!modal || !openButton || !closeButton) {
                return;
            }

            openButton.addEventListener('click', function () {
                modal.showModal();
            });

            closeButton.addEventListener('click', function () {
                modal.close();
            });

            modal.addEventListener('click', function (event) {
                const rect = modal.getBoundingClientRect();
                const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                if (!inDialog) {
                    modal.close();
                }
            });
        })();
    </script>
</body>
</html>
