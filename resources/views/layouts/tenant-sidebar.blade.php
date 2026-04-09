@php
    $roleNames = auth()->user()?->roles?->pluck('name')->all() ?? [];
    $permissionNames = auth()->user()?->getAllPermissions()?->pluck('name')->all() ?? [];
    $isOwner = in_array('Owner', $roleNames, true);
    $tenant = tenant();

    $branding = $tenant->settings['branding'] ?? [];
    $logoPath = $branding['logo_path'] ?? null;

    $links = [];

    $links[] = [
        'label' => __('Dashboard'),
        'href' => route('tenant.dashboard'),
        'active' => request()->routeIs('tenant.dashboard'),
        'show' => true,
    ];

    $links[] = [
        'label' => __('How to Brew'),
        'href' => Route::has('brewing-guides.index') ? route('brewing-guides.index') : '#',
        'active' => request()->routeIs('brewing-guides.*'),
        'show' => $tenant->canUseFeature('brewing_guides') && Route::has('brewing-guides.index') && ($isOwner || in_array('view brewing guides', $permissionNames, true)),
    ];

    $links[] = [
        'label' => __('Inventory'),
        'href' => Route::has('inventory.index') ? route('inventory.index') : '#',
        'active' => request()->routeIs('inventory.*'),
        'show' => $tenant->canUseFeature('inventory_management') && Route::has('inventory.index'),
    ];

    $links[] = [
        'label' => __('Products'),
        'href' => Route::has('products.index') ? route('products.index') : '#',
        'active' => request()->routeIs('products.*'),
        'show' => Route::has('products.index') && ($isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true)),
    ];

    $links[] = [
        'label' => __('POS'),
        'href' => Route::has('pos.index') ? route('pos.index') : '#',
        'active' => request()->routeIs('pos.*'),
        'show' => $tenant->canUseFeature('pos') && Route::has('pos.index') && ($isOwner || in_array('use pos', $permissionNames, true)),
    ];

    $links[] = [
        'label' => __('Orders'),
        'href' => Route::has('orders.index') ? route('orders.index') : '#',
        'active' => request()->routeIs('orders.*'),
        'show' => $tenant->canUseFeature('order_queue') && Route::has('orders.index') && ($isOwner || in_array('manage brewing orders', $permissionNames, true)),
    ];

    $links[] = [
        'label' => __('Sales Report'),
        'href' => Route::has('sales.index') ? route('sales.index') : '#',
        'active' => request()->routeIs('sales.*'),
        'show' => $tenant->canUseFeature('sales_reports') && Route::has('sales.index') && ($isOwner || in_array('view reports', $permissionNames, true)),
    ];

    $canManageUsers = $isOwner || in_array('manage users', $permissionNames, true);
    $canSeeStaffArea = $canManageUsers && (config('plans.' . $tenant->planKey() . '.max_users') === null || config('plans.' . $tenant->planKey() . '.max_users') > 1);

    $links[] = [
        'label' => __('Staff'),
        'href' => Route::has('users.index') ? route('users.index') : '#',
        'active' => request()->routeIs('users.*'),
        'show' => $canSeeStaffArea && Route::has('users.index'),
    ];

    $links[] = [
        'label' => __('Roles'),
        'href' => Route::has('roles.index') ? route('roles.index') : '#',
        'active' => request()->routeIs('roles.*'),
        'show' => $canSeeStaffArea && Route::has('roles.index'),
    ];

    $links[] = [
        'label' => __('User Logs'),
        'href' => Route::has('accountability.index') ? route('accountability.index') : '#',
        'active' => request()->routeIs('accountability.*'),
        'show' => $canSeeStaffArea && Route::has('accountability.index'),
    ];

    $links[] = [
        'label' => __('Support'),
        'href' => Route::has('support-tickets.create') ? route('support-tickets.create') : '#',
        'active' => request()->routeIs('support-tickets.*'),
        'show' => $canManageUsers && Route::has('support-tickets.create'),
    ];

    $links[] = [
        'label' => __('Updates'),
        'href' => Route::has('tenant.updates') ? route('tenant.updates') : '#',
        'active' => request()->routeIs('tenant.updates'),
        'show' => $isOwner && Route::has('tenant.updates'),
    ];
@endphp

@php
    $sidebarBorderClass = $sidebarBorderClass ?? 'border-r';
@endphp

<aside class="hidden w-64 shrink-0 {{ $sidebarBorderClass }} border-slate-200 bg-white sm:flex sm:min-h-screen sm:flex-col">
    <div class="flex h-16 items-center border-b border-slate-200 px-4">
        <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center gap-3">
            @if ($logoPath)
                <img src="{{ route('tenant.files.show', ['path' => $logoPath]) }}" alt="Tenant logo" class="block h-9 w-auto object-contain" />
            @else
                <x-application-logo class="block h-9 w-auto fill-current text-[color:var(--brand-primary)]" />
            @endif

            <span class="max-w-[10rem] truncate text-sm font-semibold text-slate-900">
                {{ auth()->user()?->name ?? 'User' }}
            </span>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($links as $link)
            @if ($link['show'])
                <a
                    href="{{ $link['href'] }}"
                    class="block rounded-md px-3 py-2 text-sm font-medium {{ $link['active'] ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    <div class="space-y-1 border-t border-slate-200 p-3">
        <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
            {{ __('Profile') }}
        </a>

        @if($tenant->canUseFeature('branding') && $canManageUsers)
            <a href="{{ route('branding.edit') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('branding.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ __('Branding') }}
            </a>
        @endif

        <form method="POST" action="{{ route('tenant.logout') }}" class="logout-form">
            @csrf
            <button type="button" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900" onclick="event.preventDefault(); window.BrewCloudTenantLogoutConfirm?.open(this.closest('form')); return false;">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</aside>
