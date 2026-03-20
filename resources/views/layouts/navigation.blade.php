<nav x-data="{ open: false, notificationsOpen: false }" class="bg-white border-b border-gray-100">
    @php
        $roleNames = auth()->user()?->roles?->pluck('name')->all() ?? [];
        $permissionNames = auth()->user()?->getAllPermissions()?->pluck('name')->all() ?? [];
        $isOwner = in_array('Owner', $roleNames, true);
        $canManageProducts = $isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true);
        $canManageOrders = $isOwner || in_array('manage brewing orders', $permissionNames, true);

        $inventoryRouteName = Route::has('inventory.index') ? 'inventory.index' : (Route::has('products.index') ? 'products.index' : null);
        $inventoryNotificationItems = \App\Models\Product::query()
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'stock']);
        $inventoryNotificationCount = \App\Models\Product::where('stock', '<=', 5)->count();

        $orderNotificationItems = \App\Models\Order::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(6)
            ->get(['id', 'status', 'created_at']);
        $orderNotificationCount = \App\Models\Order::where('status', 'pending')->count();
        $totalNotificationCount = $inventoryNotificationCount + $orderNotificationCount;
    @endphp
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('tenant.dashboard') }}">
                        @php
                            $branding = tenant()->settings['branding'] ?? [];
                            $logoPath = $branding['logo_path'] ?? null;
                        @endphp
                        @if ($logoPath)
                            <img src="{{ route('tenant.files.show', ['path' => $logoPath]) }}" alt="Tenant logo" class="block h-9 w-auto object-contain" />
                        @else
                            <x-application-logo class="block h-9 w-auto fill-current text-[color:var(--brand-primary)]" />
                        @endif
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('tenant.dashboard')" :active="request()->routeIs('tenant.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if(tenant()->canUseFeature('brewing_guides') && Route::has('brewing-guides.index') && ($isOwner || in_array('view brewing guides', $permissionNames, true)))
                        <x-nav-link :href="route('brewing-guides.index')" :active="request()->routeIs('brewing-guides.*')">
                            {{ __('How to Brew') }}
                        </x-nav-link>
                    @endif
                    @if(tenant()->canUseFeature('inventory_management') && Route::has('inventory.index'))
                        <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                            {{ __('Inventory') }}
                            @if ($inventoryNotificationCount > 0)
                                <span class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700">
                                    {{ $inventoryNotificationCount }}
                                </span>
                            @endif
                        </x-nav-link>
                    @endif
                    @if (Route::has('products.index') && ($isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true)))
                        <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                            {{ __('Products') }}
                            @if ($inventoryNotificationCount > 0)
                                <span class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700">
                                    {{ $inventoryNotificationCount }}
                                </span>
                            @endif
                        </x-nav-link>
                    @endif
                    @if (tenant()->canUseFeature('pos') && Route::has('pos.index') && ($isOwner || in_array('use pos', $permissionNames, true)))
                        <x-nav-link :href="route('pos.index')" :active="request()->routeIs('pos.*')">
                            {{ __('POS') }}
                        </x-nav-link>
                    @endif
                    @if (tenant()->canUseFeature('order_queue') && Route::has('orders.index') && ($isOwner || in_array('manage brewing orders', $permissionNames, true)))
                        <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                            {{ __('Orders') }}
                            @if ($orderNotificationCount > 0)
                                <span class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-700">
                                    {{ $orderNotificationCount }}
                                </span>
                            @endif
                        </x-nav-link>
                    @endif
                    @if (tenant()->canUseFeature('sales_reports') && Route::has('sales.index') && ($isOwner || in_array('view reports', $permissionNames, true)))
                        <x-nav-link :href="route('sales.index')" :active="request()->routeIs('sales.*')">
                            {{ __('Sales Report') }}
                        </x-nav-link>
                    @endif
                    @if (($isOwner || in_array('manage users', $permissionNames, true)) && (config('plans.' . tenant()->planKey() . '.max_users') === null || config('plans.' . tenant()->planKey() . '.max_users') > 1))
                        @if (Route::has('users.index'))
                            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                {{ __('Staff') }}
                            </x-nav-link>
                        @endif
                        @if (Route::has('roles.index'))
                            <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                                {{ __('Roles') }}
                            </x-nav-link>
                        @endif
                        @if (Route::has('accountability.index'))
                            <x-nav-link :href="route('accountability.index')" :active="request()->routeIs('accountability.*')">
                                {{ __('User Logs') }}
                            </x-nav-link>
                        @endif
                    @endif
                    @if (($isOwner || in_array('manage users', $permissionNames, true)) && Route::has('support-tickets.create'))
                        <x-nav-link :href="route('support-tickets.create')" :active="request()->routeIs('support-tickets.*')">
                            {{ __('Support') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Notification + Settings -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="relative" @click.outside="notificationsOpen = false">
                    <button
                        type="button"
                        @click="notificationsOpen = !notificationsOpen"
                        class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                        aria-label="Notifications"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if ($totalNotificationCount > 0)
                            <span class="absolute -right-1 -top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-rose-100 px-1 py-0.5 text-[10px] font-semibold leading-none text-rose-700">
                                {{ $totalNotificationCount }}
                            </span>
                        @endif
                    </button>

                    <div
                        x-cloak
                        x-show="notificationsOpen"
                        x-transition
                        class="absolute right-0 z-50 mt-2 w-80 rounded-md border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="text-sm font-semibold text-gray-800">Notifications</p>
                            <p class="text-xs text-gray-500">{{ $totalNotificationCount }} active alert{{ $totalNotificationCount === 1 ? '' : 's' }}</p>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            @if ($totalNotificationCount === 0)
                                <div class="px-4 py-4 text-sm text-gray-500">No new notifications.</div>
                            @else
                                @if ($canManageProducts && $inventoryNotificationCount > 0)
                                    <div class="border-b border-gray-100 px-4 py-3">
                                        <div class="mb-2 flex items-center justify-between">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory</p>
                                            @if ($inventoryRouteName)
                                                <a href="{{ route($inventoryRouteName) }}" class="text-xs font-medium text-[color:var(--brand-primary)] hover:underline">View all</a>
                                            @endif
                                        </div>
                                        <ul class="space-y-1.5">
                                            @foreach ($inventoryNotificationItems as $inventoryItem)
                                                <li class="text-sm text-gray-700">
                                                    <span class="font-medium">{{ $inventoryItem->name }}</span>
                                                    <span class="text-gray-500">• stock: {{ $inventoryItem->stock }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($canManageOrders && $orderNotificationCount > 0)
                                    <div class="px-4 py-3">
                                        <div class="mb-2 flex items-center justify-between">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orders</p>
                                            @if (Route::has('orders.index'))
                                                <a href="{{ route('orders.index') }}" class="text-xs font-medium text-[color:var(--brand-primary)] hover:underline">View all</a>
                                            @endif
                                        </div>
                                        <ul class="space-y-1.5">
                                            @foreach ($orderNotificationItems as $orderItem)
                                                <li>
                                                    @if (Route::has('orders.show'))
                                                        <a href="{{ route('orders.show', $orderItem->id) }}" class="text-sm text-gray-700 hover:text-gray-900">
                                                            Order #{{ $orderItem->id }}
                                                            <span class="text-gray-500">• pending</span>
                                                        </a>
                                                    @else
                                                        <span class="text-sm text-gray-700">
                                                            Order #{{ $orderItem->id }}
                                                            <span class="text-gray-500">• pending</span>
                                                        </span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()?->name ?? 'User' }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        @if(tenant()->canUseFeature('branding') && ($isOwner || in_array('manage users', $permissionNames, true)))
                            <x-dropdown-link :href="route('branding.edit')">
                                {{ __('Branding') }}
                            </x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('tenant.logout') }}" class="logout-form">
                            @csrf
                            <button type="button" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="event.preventDefault(); window.BrewCloudTenantLogoutConfirm?.open(this.closest('form')); return false;">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('tenant.dashboard')" :active="request()->routeIs('tenant.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if(tenant()->canUseFeature('brewing_guides') && Route::has('brewing-guides.index') && ($isOwner || in_array('view brewing guides', $permissionNames, true)))
                <x-responsive-nav-link :href="route('brewing-guides.index')" :active="request()->routeIs('brewing-guides.*')">
                    {{ __('How to Brew') }}
                </x-responsive-nav-link>
            @endif
            @if(tenant()->canUseFeature('inventory_management') && Route::has('inventory.index'))
                <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                    {{ __('Inventory') }}
                    @if ($inventoryNotificationCount > 0)
                        <span class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700">
                            {{ $inventoryNotificationCount }}
                        </span>
                    @endif
                </x-responsive-nav-link>
            @endif
            @if (Route::has('products.index') && ($isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true)))
                <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                    {{ __('Products') }}
                    @if ($inventoryNotificationCount > 0)
                        <span class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700">
                            {{ $inventoryNotificationCount }}
                        </span>
                    @endif
                </x-responsive-nav-link>
            @endif
            @if (tenant()->canUseFeature('pos') && Route::has('pos.index') && ($isOwner || in_array('use pos', $permissionNames, true)))
                <x-responsive-nav-link :href="route('pos.index')" :active="request()->routeIs('pos.*')">
                    {{ __('POS') }}
                </x-responsive-nav-link>
            @endif
            @if (tenant()->canUseFeature('order_queue') && Route::has('orders.index') && ($isOwner || in_array('manage brewing orders', $permissionNames, true)))
                <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                    {{ __('Orders') }}
                    @if ($orderNotificationCount > 0)
                        <span class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-700">
                            {{ $orderNotificationCount }}
                        </span>
                    @endif
                </x-responsive-nav-link>
            @endif
            @if (tenant()->canUseFeature('sales_reports') && Route::has('sales.index') && ($isOwner || in_array('view reports', $permissionNames, true)))
                <x-responsive-nav-link :href="route('sales.index')" :active="request()->routeIs('sales.*')">
                    {{ __('Sales Report') }}
                </x-responsive-nav-link>
            @endif
            @if (($isOwner || in_array('manage users', $permissionNames, true)) && (config('plans.' . tenant()->planKey() . '.max_users') === null || config('plans.' . tenant()->planKey() . '.max_users') > 1))
                @if (Route::has('users.index'))
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('Staff') }}
                    </x-responsive-nav-link>
                @endif
                @if (Route::has('roles.index'))
                    <x-responsive-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                        {{ __('Roles') }}
                    </x-responsive-nav-link>
                @endif
                @if (Route::has('accountability.index'))
                    <x-responsive-nav-link :href="route('accountability.index')" :active="request()->routeIs('accountability.*')">
                        {{ __('Accountability') }}
                    </x-responsive-nav-link>
                @endif
            @endif
            @if (($isOwner || in_array('manage users', $permissionNames, true)) && Route::has('support-tickets.create'))
                <x-responsive-nav-link :href="route('support-tickets.create')" :active="request()->routeIs('support-tickets.*')">
                    {{ __('Support') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()?->name ?? 'User' }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                @if(tenant()->canUseFeature('branding') && ($isOwner || in_array('manage users', $permissionNames, true)))
                    <x-responsive-nav-link :href="route('branding.edit')">
                        {{ __('Branding') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('tenant.logout') }}" class="logout-form">
                    @csrf
                    <button type="button" class="block w-full text-left px-4 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50" onclick="event.preventDefault(); window.BrewCloudTenantLogoutConfirm?.open(this.closest('form')); return false;">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <dialog id="tenant-logout-confirm-modal" class="w-full max-w-md rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
        <div class="rounded-xl bg-white p-6">
            <h3 class="text-base font-semibold text-slate-900">Confirm Logout</h3>
            <p class="mt-2 text-sm text-slate-600">Are you sure you want to log out?</p>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-tenant-logout-cancel class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" data-tenant-logout-confirm class="rounded-md bg-[color:var(--brand-primary)] px-3 py-2 text-sm font-medium text-white hover:opacity-90">Log Out</button>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const logoutModal = document.getElementById('tenant-logout-confirm-modal');
            const cancelButton = document.querySelector('[data-tenant-logout-cancel]');
            const confirmButton = document.querySelector('[data-tenant-logout-confirm]');

            if (!logoutModal || !cancelButton || !confirmButton) {
                return;
            }

            let pendingForm = null;

            window.BrewCloudTenantLogoutConfirm = {
                open(formElement) {
                    if (formElement instanceof HTMLFormElement) {
                        pendingForm = formElement;
                    } else if (formElement instanceof HTMLElement) {
                        pendingForm = formElement.closest('form');
                    } else {
                        // Fallback: find any logout form
                        pendingForm = document.querySelector('form[action*="logout"]');
                    }

                    if (pendingForm) {
                        logoutModal.showModal();
                    }
                },
            };

            cancelButton.addEventListener('click', () => {
                pendingForm = null;
                logoutModal.close();
            });

            confirmButton.addEventListener('click', () => {
                if (pendingForm) {
                    pendingForm.submit();
                }
            });

            logoutModal.addEventListener('click', (event) => {
                const rect = logoutModal.getBoundingClientRect();
                const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

                if (!inDialog) {
                    pendingForm = null;
                    logoutModal.close();
                }
            });
        })();
    </script>
</nav>
