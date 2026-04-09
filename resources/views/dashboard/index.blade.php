<x-app-layout>
    <x-slot name="header">
        <div class="dashboard-header">
            <div>
                <h2 class="dashboard-title">{{ __('Dashboard') }}</h2>
                <p class="dashboard-subtitle">{{ tenant()->name }}</p>
            </div>
            @if ($canCustomizeDashboard)
                <button
                    type="button"
                    class="modal-close"
                    onclick="window.dispatchEvent(new CustomEvent('toggle-dashboard-customize'))"
                >
                    Customize Dashboard
                </button>
            @endif
        </div>
    </x-slot>

    <div
        class="dashboard-shell py-8"
        x-data="dashboardPage({
            initialLayout: @js($dashboardLayout),
            widgets: @js($availableWidgets),
            customSections: @js($customSections),
            initialNavigationPosition: @js($navigationPosition),
            saveUrl: '{{ route('tenant.dashboard.layout.update') }}',
            resetUrl: '{{ route('tenant.dashboard.layout.reset') }}',
            canCustomize: @js($canCustomizeDashboard),
        })"
        @toggle-dashboard-customize.window="if (canCustomize) customizeOpen = !customizeOpen"
    >
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
            $planBandwidthLabels = [
                'basic' => '10 GB/month',
                'starter' => '10 GB/month',
                'standard' => '20 GB/month',
                'business' => 'Unlimited',
            ];
            $planBandwidthLabel = $planBandwidthLabels[$planKey] ?? null;
            $planNameWithBandwidth = $planBandwidthLabel ? $planName . ' (' . $planBandwidthLabel . ')' : $planName;
            $planPrice = $plan['price'] ?? null;
            $maxUsers = $plan['max_users'] ?? null;

            $roleNames = auth()->user()?->roles?->pluck('name')->all() ?? [];
            $permissionNames = auth()->user()?->getAllPermissions()?->pluck('name')->all() ?? [];
            $isOwner = in_array('Owner', $roleNames, true);
            $featureNav = [
                'pos' => ['label' => 'Open POS', 'route' => 'pos.index', 'permissions' => ['use pos']],
                'product_management' => ['label' => 'Manage Products', 'route' => 'products.index', 'permissions' => ['view products', 'manage products']],
                'inventory_management' => ['label' => 'Inventory', 'route' => 'inventory.index', 'permissions' => []],
            ];

            $topWidgets = $dashboardLayout['top'] ?? [];
            $bottomWidgets = $dashboardLayout['bottom'] ?? [];
            $orderedWidgets = array_values(array_unique(array_merge($topWidgets, $bottomWidgets)));
            $customSectionMap = collect($customSections ?? [])->keyBy('id')->all();
            $navigationPosition = $navigationPosition ?? 'top';
        @endphp

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($canCustomizeDashboard)
                <div x-cloak x-show="customizeOpen" class="mb-6 dashboard-panel p-6 space-y-4">
                    <div class="dashboard-header">
                        <div>
                            <div class="dashboard-section-title">Customize Dashboard</div>
                            <p class="dashboard-subtitle">Arrange components in one canvas, remove, or add new ones from the palette.</p>
                        </div>
                    </div>

                    <div class="max-w-4xl mx-auto space-y-4">
                        <template x-for="sectionKey in ['top', 'bottom']" :key="sectionKey">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="dashboard-section-title" x-text="sectionKey === 'top' ? 'Top Section' : 'Bottom Section'"></div>
                                <div
                                    class="mt-3 min-h-32 rounded-lg border border-dashed border-slate-300 p-3 space-y-2"
                                    @dragover.prevent
                                    @drop.prevent="dropToSection(sectionKey)"
                                >
                                    <template x-if="layout[sectionKey].length === 0">
                                        <div class="text-sm text-slate-400">No widgets in this section.</div>
                                    </template>

                                    <template x-for="(widgetId, index) in layout[sectionKey]" :key="sectionKey + '-' + widgetId + '-' + index">
                                        <div
                                            class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                                            draggable="true"
                                            @dragstart="startDrag(widgetId, sectionKey)"
                                            @dragover.prevent
                                            @drop.prevent="dropOnWidget(sectionKey, index)"
                                        >
                                            <div class="flex items-center gap-2 text-sm text-slate-700">
                                                <span class="cursor-move select-none">☰</span>
                                                <span x-text="widgetLabel(widgetId)"></span>
                                            </div>
                                            <button type="button" class="text-xs text-rose-600 hover:text-rose-700" @click="removeWidget(sectionKey, widgetId)">Remove</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="max-w-4xl mx-auto rounded-xl border border-slate-200 bg-white p-4">
                        <div class="dashboard-section-title">Dashboard Settings</div>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 p-3">
                                <label class="dashboard-section-title" for="navigation_position">Navigation Position</label>
                                <select
                                    id="navigation_position"
                                    class="mt-2 w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    x-model="navigationPosition"
                                >
                                    <option value="top">Top</option>
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                </select>
                                <p class="mt-2 text-xs text-slate-500">Choose where tenant navigation appears on desktop.</p>
                            </div>
                        </div>

                        <div class="dashboard-section-title">Widget Palette</div>
                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <template x-for="(meta, widgetId) in widgets" :key="widgetId">
                                <div class="rounded-lg border border-slate-200 p-3">
                                    <div class="text-sm font-medium text-slate-800" x-text="meta.label"></div>
                                    <p class="mt-1 text-xs text-slate-500" x-text="meta.description"></p>
                                    <div class="mt-2">
                                        <button type="button" class="modal-close text-xs" @click="addWidget(widgetId, 'top')" :disabled="isUsed(widgetId)">Add Top</button>
                                        <button type="button" class="modal-close text-xs" @click="addWidget(widgetId, 'bottom')" :disabled="isUsed(widgetId)">Add Bottom</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 rounded-lg border border-slate-200 p-3">
                            <div class="text-sm font-medium text-slate-800">Add Custom Section</div>
                            <p class="mt-1 text-xs text-slate-500">Create your own dashboard section and place it in top or bottom.</p>
                            <div class="mt-3 grid grid-cols-1 gap-3">
                                <input
                                    type="text"
                                    class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Section title"
                                    x-model.trim="newCustomSection.title"
                                    maxlength="80"
                                >
                                <textarea
                                    class="w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    rows="3"
                                    placeholder="Section content"
                                    x-model.trim="newCustomSection.content"
                                    maxlength="500"
                                ></textarea>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="modal-close text-xs" @click="createCustomSection('top')">Add to Top</button>
                                    <button type="button" class="modal-close text-xs" @click="createCustomSection('bottom')">Add to Bottom</button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg border border-slate-200 p-3" x-show="customSections.length > 0">
                            <div class="text-sm font-medium text-slate-800">Edit Custom Sections</div>
                            <p class="mt-1 text-xs text-slate-500">Update title/content below, then click Save Layout.</p>

                            <div class="mt-3 space-y-3">
                                <template x-for="section in customSections" :key="'edit-' + section.id">
                                    <div class="rounded-lg border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500" x-text="section.id"></div>
                                        <input
                                            type="text"
                                            class="mt-2 w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Section title"
                                            x-model.trim="section.title"
                                            maxlength="80"
                                        >
                                        <textarea
                                            class="mt-2 w-full rounded-md border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            rows="3"
                                            placeholder="Section content"
                                            x-model.trim="section.content"
                                            maxlength="500"
                                        ></textarea>
                                        <div class="mt-2">
                                            <button type="button" class="text-xs text-rose-600 hover:text-rose-700" @click="removeCustomSection(section.id)">Delete Section</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" class="modal-close" @click="resetLayout" x-bind:disabled="saving">Reset Default</button>
                        <x-primary-button type="button" @click="saveLayout" x-bind:disabled="saving" x-text="saving ? 'Saving...' : 'Save Layout'"></x-primary-button>
                    </div>

                    <div x-show="saveMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" x-text="saveMessage"></div>
                    <div x-show="saveError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="saveError"></div>
                </div>
            @endif

            <div class="max-w-4xl mx-auto space-y-6">
                @foreach ($orderedWidgets as $widget)
                    @if ($widget === 'welcome')
                        <div class="dashboard-panel overflow-hidden p-6">
                            <div class="text-slate-700">{{ __('Welcome to your coffee shop management dashboard!') }}</div>
                        </div>
                    @endif

                    @if ($widget === 'quick_actions')
                        <div class="dashboard-panel overflow-hidden p-6">
                            <div class="dashboard-section-title">Quick Actions</div>
                            <div class="mt-4 dashboard-grid">
                                @if (tenant()->canUseFeature('brewing_guides') && Route::has('brewing-guides.index') && ($isOwner || in_array('view brewing guides', $permissionNames, true)))
                                    <a href="{{ route('brewing-guides.index') }}" class="action-card">How to Brew</a>
                                @endif
                                @foreach ($featureNav as $featureKey => $nav)
                                    @php
                                        $canSeeAction = $isOwner || empty($nav['permissions']) || count(array_intersect($nav['permissions'], $permissionNames)) > 0;
                                    @endphp
                                    @if (tenant()->canUseFeature($featureKey) && \Illuminate\Support\Facades\Route::has($nav['route']) && $canSeeAction)
                                        <a href="{{ route($nav['route']) }}" class="action-card">{{ $nav['label'] }}</a>
                                    @endif
                                @endforeach
                                @if (tenant()->canUseFeature('sales_reports') && Route::has('sales.index') && ($isOwner || in_array('view reports', $permissionNames, true)))
                                    <a href="{{ route('sales.index') }}" class="action-card">Sales Reports</a>
                                @endif
                                @if (tenant()->canUseFeature('order_queue') && Route::has('orders.index') && ($isOwner || in_array('manage brewing orders', $permissionNames, true)))
                                    <a href="{{ route('orders.index') }}" class="action-card">Orders Queue</a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($widget === 'today_glance')
                        <div class="dashboard-panel overflow-hidden p-6">
                            <div class="dashboard-section-title">Today at a glance</div>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-1 xl:grid-cols-3">
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
                    @endif

                    @if ($widget === 'plan_summary' && $isOwner)
                        <div class="dashboard-panel overflow-hidden p-6">
                            <div class="dashboard-section-title">Plan Summary</div>
                            <div class="mt-2 text-slate-700">Current plan: {{ $planNameWithBandwidth }}</div>
                            @if ($planPrice)
                                <div class="text-sm text-slate-500">₱{{ number_format($planPrice) }} / month</div>
                            @endif
                            <div class="mt-4">
                                <button type="button" class="modal-close" @click="planOpen = true">Plan overview</button>
                            </div>
                        </div>
                    @endif

                    @if (str_starts_with($widget, 'custom:'))
                        @php
                            $customSection = $customSectionMap[$widget] ?? null;
                        @endphp
                        @if ($customSection)
                            <div class="dashboard-panel overflow-hidden p-6">
                                <div class="dashboard-section-title">{{ $customSection['title'] }}</div>
                                @if (!empty($customSection['content']))
                                    <div class="mt-2 text-slate-700 whitespace-pre-line">{{ $customSection['content'] }}</div>
                                @endif
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>

            @if (empty($orderedWidgets))
                <div class="dashboard-panel overflow-hidden p-6 mt-6">
                    <div class="text-slate-600">No dashboard widgets configured.</div>
                </div>
            @endif
        </div>

        <div x-cloak x-show="planOpen" class="modal-backdrop" @click="planOpen = false"></div>
        <div x-cloak x-show="planOpen" class="modal-panel" role="dialog" aria-modal="true" aria-label="Plan overview">
            <div class="modal-card" @click.stop>
                <div class="dashboard-header">
                    <div>
                        <div class="modal-title">Current Plan: {{ $planNameWithBandwidth }}</div>
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

        <script>
            function dashboardPage({ initialLayout, initialNavigationPosition, widgets, customSections, saveUrl, resetUrl, canCustomize }) {
                const clone = (value) => JSON.parse(JSON.stringify(value));

                return {
                    planOpen: false,
                    customizeOpen: false,
                    widgets,
                    saveUrl,
                    resetUrl,
                    canCustomize,
                    saving: false,
                    saveMessage: '',
                    saveError: '',
                    dragContext: null,
                    layout: clone(initialLayout),
                    defaultLayout: clone(initialLayout),
                    navigationPosition: ['top', 'left', 'right'].includes(initialNavigationPosition) ? initialNavigationPosition : 'top',
                    customSections: Array.isArray(customSections) ? clone(customSections) : [],
                    newCustomSection: { title: '', content: '' },
                    isUsed(widgetId) {
                        return this.layout.top.includes(widgetId) || this.layout.bottom.includes(widgetId);
                    },
                    widgetLabel(widgetId) {
                        const baseWidget = this.widgets?.[widgetId];
                        if (baseWidget?.label) {
                            return baseWidget.label;
                        }

                        const custom = this.customSections.find((section) => section.id === widgetId);
                        if (custom?.title) {
                            return `Custom: ${custom.title}`;
                        }

                        return widgetId;
                    },
                    addWidget(widgetId, sectionKey) {
                        if (this.isUsed(widgetId)) {
                            return;
                        }

                        this.layout[sectionKey].push(widgetId);
                    },
                    removeWidget(sectionKey, widgetId) {
                        this.layout[sectionKey] = this.layout[sectionKey].filter((entry) => entry !== widgetId);

                        if (String(widgetId).startsWith('custom:')) {
                            this.layout.top = this.layout.top.filter((entry) => entry !== widgetId);
                            this.layout.bottom = this.layout.bottom.filter((entry) => entry !== widgetId);
                            this.customSections = this.customSections.filter((section) => section.id !== widgetId);
                        }
                    },
                    createCustomSection(sectionKey) {
                        const title = (this.newCustomSection.title || '').trim();
                        const content = (this.newCustomSection.content || '').trim();

                        if (!title) {
                            this.saveError = 'Custom section title is required.';
                            return;
                        }

                        this.saveError = '';

                        const slug = title
                            .toLowerCase()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '') || `section-${Date.now()}`;

                        let widgetId = `custom:${slug}`;
                        let suffix = 1;
                        while (this.customSections.some((section) => section.id === widgetId)) {
                            widgetId = `custom:${slug}-${suffix}`;
                            suffix += 1;
                        }

                        this.customSections.push({
                            id: widgetId,
                            title: title.slice(0, 80),
                            content: content.slice(0, 500),
                        });

                        this.addWidget(widgetId, sectionKey);
                        this.newCustomSection = { title: '', content: '' };
                    },
                    removeCustomSection(sectionId) {
                        this.layout.top = this.layout.top.filter((entry) => entry !== sectionId);
                        this.layout.bottom = this.layout.bottom.filter((entry) => entry !== sectionId);
                        this.customSections = this.customSections.filter((section) => section.id !== sectionId);
                    },
                    normalizeCustomSectionsForSave() {
                        const normalized = [];

                        for (const section of this.customSections) {
                            const title = String(section?.title || '').trim();
                            const content = String(section?.content || '').trim();
                            const sectionId = String(section?.id || '').trim();

                            if (!title || !sectionId) {
                                continue;
                            }

                            normalized.push({
                                id: sectionId,
                                title: title.slice(0, 80),
                                content: content.slice(0, 500),
                            });
                        }

                        return normalized;
                    },
                    startDrag(widgetId, sectionKey) {
                        this.dragContext = { widgetId, from: sectionKey };
                    },
                    dropToSection(sectionKey) {
                        if (!this.dragContext) {
                            return;
                        }

                        const { widgetId, from } = this.dragContext;
                        this.layout[from] = this.layout[from].filter((entry) => entry !== widgetId);
                        this.layout[sectionKey].push(widgetId);
                        this.dragContext = null;
                    },
                    dropOnWidget(sectionKey, targetIndex) {
                        if (!this.dragContext) {
                            return;
                        }

                        const { widgetId, from } = this.dragContext;
                        this.layout[from] = this.layout[from].filter((entry) => entry !== widgetId);

                        const nextList = [...this.layout[sectionKey]];
                        nextList.splice(targetIndex, 0, widgetId);
                        this.layout[sectionKey] = nextList;

                        this.dragContext = null;
                    },
                    async resetLayout() {
                        if (!this.canCustomize) {
                            return;
                        }

                        this.saving = true;
                        this.saveMessage = '';
                        this.saveError = '';

                        try {
                            const response = await fetch(this.resetUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                            });

                            const payload = await response.json();

                            if (!response.ok) {
                                throw new Error(payload.message || 'Unable to reset dashboard layout.');
                            }

                            this.layout = clone(payload.layout || this.defaultLayout);
                            this.customSections = Array.isArray(payload.custom_sections) ? clone(payload.custom_sections) : [];
                            this.navigationPosition = ['top', 'left', 'right'].includes(payload.navigation_position) ? payload.navigation_position : this.navigationPosition;
                            this.defaultLayout = clone(this.layout);
                            this.saveMessage = payload.message || 'Dashboard layout reset to default.';

                            window.setTimeout(() => {
                                window.location.reload();
                            }, 700);
                        } catch (error) {
                            this.saveError = error.message || 'Unable to reset dashboard layout.';
                        } finally {
                            this.saving = false;
                        }
                    },
                    async saveLayout() {
                        if (!this.canCustomize) {
                            return;
                        }

                        this.customSections = this.normalizeCustomSectionsForSave();

                        this.saving = true;
                        this.saveMessage = '';
                        this.saveError = '';

                        try {
                            const response = await fetch(this.saveUrl, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    layout: this.layout,
                                    custom_sections: this.customSections,
                                    navigation_position: this.navigationPosition,
                                }),
                            });

                            const payload = await response.json();

                            if (!response.ok) {
                                throw new Error(payload.message || 'Unable to save dashboard layout.');
                            }

                            this.layout = clone(payload.layout || this.layout);
                            this.customSections = Array.isArray(payload.custom_sections) ? clone(payload.custom_sections) : this.customSections;
                            this.navigationPosition = ['top', 'left', 'right'].includes(payload.navigation_position) ? payload.navigation_position : this.navigationPosition;
                            this.defaultLayout = clone(this.layout);
                            this.saveMessage = payload.message || 'Dashboard layout saved.';

                            window.setTimeout(() => {
                                window.location.reload();
                            }, 700);
                        } catch (error) {
                            this.saveError = error.message || 'Unable to save dashboard layout.';
                        } finally {
                            this.saving = false;
                        }
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
