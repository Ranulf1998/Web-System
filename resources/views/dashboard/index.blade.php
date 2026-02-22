<x-app-layout>
    <x-slot name="header">
        <div class="dashboard-header">
            <div>
                <h2 class="dashboard-title">
                    {{ __('Dashboard') }}
                </h2>
                <p class="dashboard-subtitle">{{ tenant()->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="dashboard-shell py-8" x-data="{ planOpen: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="dashboard-panel overflow-hidden">
                <div class="p-6">
                    <div class="text-slate-700">
                        {{ __('Welcome to your coffee shop management dashboard!') }}
                    </div>

                    @php
                        $planKey = tenant()->planKey();
                        $plan = config('plans.' . $planKey, []);
                        $featureLabels = [
                            'pos' => 'POS system',
                            'product_management' => 'Product management',
                            'brewing_guides' => 'How to brew guides',
                            'basic_sales_tracking' => 'Basic sales tracking',
                            'basic_reports' => 'Basic reports',
                            'order_queue' => 'Order queue',
                            'inventory_management' => 'Inventory management',
                            'sales_reports' => 'Sales reports',
                            'sales_dashboard' => 'Sales dashboard',
                            'inventory_alerts' => 'Inventory alerts',
                            'advanced_analytics' => 'Advanced analytics',
                            'multi_branch_support' => 'Multi-branch support',
                            'priority_support' => 'Priority support',
                            'branding' => 'Branding customization',
                        ];
                        $planFeatures = $plan['features'] ?? [];
                        $planName = $plan['name'] ?? ucfirst($planKey);
                        $planPrice = $plan['price'] ?? null;
                        $maxUsers = $plan['max_users'] ?? null;
                    @endphp

                    <div class="mt-6">
                        <button type="button" class="modal-close" @click="planOpen = true">
                            Plan overview
                        </button>
                    </div>

                    @php
                        $roleNames = auth()->user()?->roles?->pluck('name')->all() ?? [];
                        $permissionNames = auth()->user()?->getAllPermissions()?->pluck('name')->all() ?? [];
                        $isOwner = in_array('Owner', $roleNames, true);
                        $featureNav = [
                            'pos' => ['label' => 'Open POS', 'route' => 'pos.index', 'permissions' => ['use pos']],
                            'product_management' => ['label' => 'Manage Products', 'route' => 'products.index', 'permissions' => ['view products', 'manage products']],
                            'inventory_management' => ['label' => 'Inventory', 'route' => 'inventory.index', 'permissions' => []],
                        ];
                    @endphp

                    <div class="mt-8">
                        <div class="dashboard-section-title">Quick Actions</div>
                        <div class="mt-4 dashboard-grid">
                            @if (tenant()->canUseFeature('brewing_guides') && Route::has('brewing-guides.index') && ($isOwner || in_array('view brewing guides', $permissionNames, true)))
                                <a href="{{ route('brewing-guides.index') }}"
                                   class="action-card">
                                    How to Brew
                                </a>
                            @endif
                            @foreach ($featureNav as $featureKey => $nav)
                                @php
                                    $canSeeAction = $isOwner || empty($nav['permissions']) || count(array_intersect($nav['permissions'], $permissionNames)) > 0;
                                @endphp
                                @if (tenant()->canUseFeature($featureKey) && \Illuminate\Support\Facades\Route::has($nav['route']) && $canSeeAction)
                                    <a href="{{ route($nav['route']) }}"
                                       class="action-card">
                                        {{ $nav['label'] }}
                                    </a>
                                @endif
                            @endforeach
                            @if (tenant()->canUseFeature('sales_reports') && Route::has('sales.index') && ($isOwner || in_array('view reports', $permissionNames, true)))
                                <a href="{{ route('sales.index') }}"
                                   class="action-card">
                                    Sales Reports
                                </a>
                            @endif
                            @if (tenant()->canUseFeature('order_queue') && Route::has('orders.index') && ($isOwner || in_array('manage brewing orders', $permissionNames, true)))
                                <a href="{{ route('orders.index') }}"
                                   class="action-card">
                                    Orders Queue
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-8">
                        <div class="dashboard-section-title">Today at a glance</div>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            @if ($isOwner || in_array('view products', $permissionNames, true) || in_array('manage products', $permissionNames, true))
                                <div class="stat-card">
                                    <div class="stat-label">Total Products</div>
                                    <div class="stat-value">{{ \App\Models\Product::count() }}</div>
                                    <div class="stat-meta">Active catalog items</div>
                                </div>
                            @endif

                            @if ($isOwner || in_array('view reports', $permissionNames, true))
                                <div class="stat-card">
                                    <div class="stat-label">Today's Sales</div>
                                    <div class="stat-value">₱{{ number_format(\App\Models\Order::whereDate('created_at', today())->sum('total'), 2) }}</div>
                                    <div class="stat-meta">Gross revenue today</div>
                                </div>
                            @endif

                            @if (($isOwner || in_array('manage users', $permissionNames, true)) && (config('plans.' . tenant()->planKey() . '.max_users') === null || config('plans.' . tenant()->planKey() . '.max_users') > 1))
                                <div class="stat-card">
                                    <div class="stat-label">Active Users</div>
                                    <div class="stat-value">{{ \App\Models\User::count() }}</div>
                                    <div class="stat-meta">Staff on your tenant</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-cloak x-show="planOpen" class="modal-backdrop" @click="planOpen = false"></div>
        <div x-cloak x-show="planOpen" class="modal-panel" role="dialog" aria-modal="true" aria-label="Plan overview">
            <div class="modal-card" @click.stop>
                <div class="dashboard-header">
                    <div>
                        <div class="modal-title">Current Plan: {{ $planName }}</div>
                        @if ($planPrice)
                            <div class="dashboard-subtitle">₱{{ number_format($planPrice) }} / month</div>
                        @endif
                    </div>
                    <button type="button" class="modal-close" @click="planOpen = false">Close</button>
                </div>
                <div class="dashboard-card-body">
                    @if ($maxUsers === null)
                        Staff accounts: Unlimited
                    @else
                        Staff accounts: Up to {{ $maxUsers }}
                    @endif
                </div>
                <div class="mt-4">
                    <div class="dashboard-section-title">Included Features</div>
                    <ul class="modal-list">
                        @foreach ($planFeatures as $feature)
                            <li>{{ $featureLabels[$feature] ?? $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
