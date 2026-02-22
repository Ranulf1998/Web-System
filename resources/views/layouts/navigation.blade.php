<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $roleNames = auth()->user()?->roles?->pluck('name')->all() ?? [];
        $permissionNames = auth()->user()?->getAllPermissions()?->pluck('name')->all() ?? [];
        $isOwner = in_array('Owner', $roleNames, true);
    @endphp
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
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
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
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
                        </x-nav-link>
                    @endif
                    @if (Route::has('products.index') && ($isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true)))
                        <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                            {{ __('Products') }}
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
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

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
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
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
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
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
                </x-responsive-nav-link>
            @endif
            @if (Route::has('products.index') && ($isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true)))
                <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                    {{ __('Products') }}
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
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
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
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
