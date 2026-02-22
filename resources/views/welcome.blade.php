<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BrewCloud – Multi-Tenant Coffee Shop Management</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles (include your existing Tailwind or Vite) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* Include the full Tailwind CSS you already have – I'm omitting it here for brevity,
               but you should keep the exact <style> block from your original file.
               In practice, you'll use Vite to compile your CSS. */
            /* ... your existing Tailwind styles ... */
        </style>
    @endif
</head>
<body class="bg-gradient-to-br from-[#f8f4ee] via-[#f6efe6] via-35% to-[#e8dff6] text-[#1c1b16] min-h-screen">
    <div class="relative overflow-hidden" x-data="{ plansOpen: false }" @keydown.escape.window="plansOpen = false">
        <div class="pointer-events-none absolute -top-24 -right-20 h-72 w-72 rounded-full bg-[#d9b38c]/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -top-20 left-1/3 h-64 w-64 rounded-full bg-[#a8d5ba]/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-10 h-80 w-80 rounded-full bg-[#9f6f4e]/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 right-1/4 h-72 w-72 rounded-full bg-[#b7c8ff]/20 blur-3xl"></div>

        <header class="mx-auto w-full max-w-6xl px-6 pt-6 lg:px-10">
            <nav class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#1c1b16] text-white">
                        BC
                    </span>
                    <div>
                        <div class="text-lg font-semibold">BrewCloud</div>
                        <div class="text-xs uppercase tracking-[0.2em] text-[#7d6d5a]">Coffee SaaS</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <form method="GET" action="{{ url('/shop-login') }}" class="flex items-center gap-2 rounded-full bg-white/70 px-3 py-2 shadow-sm ring-1 ring-[#e6d9c7]">
                        <span class="text-xs uppercase tracking-wide text-[#7d6d5a]">Shop</span>
                        <input type="text" name="subdomain" placeholder="Enter Shop" required
                               class="w-28 bg-transparent text-sm text-[#1c1b16] placeholder:text-[#9b8b7a] focus:outline-none">
                        <button type="submit" class="rounded-full bg-gradient-to-r from-[#1c1b16] to-[#6b4f3a] px-3 py-1 text-xs font-semibold text-white">Login</button>
                    </form>
                    <a href="{{ url('/super-admin/login') }}"
                       class="rounded-full border border-[#5f4f97] bg-[#ede9ff] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#4a3d82] transition hover:bg-[#5f4f97] hover:text-white">
                        Super Admin Login
                    </a>
                    <a href="{{ route('tenant.register') }}"
                       class="rounded-full border border-[#1c1b16] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#1c1b16] transition hover:bg-[#1c1b16] hover:text-white">
                        Register shop
                    </a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 pb-16 pt-12 lg:px-10">
            <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-white/80 to-[#f4efff] px-4 py-1 text-xs uppercase tracking-[0.3em] text-[#7d6d5a] ring-1 ring-[#ddd2f6]">
                        New: multi-tenant coffee platform
                    </div>
                    <h1 class="text-4xl font-semibold leading-tight text-[#1c1b16] sm:text-5xl">
                        Run every cafe like a flagship store.
                    </h1>
                    <p class="text-base text-[#5e5246] sm:text-lg">
                        BrewCloud brings POS, inventory, staff, and analytics into a single workspace.
                        Launch new locations in minutes and keep every shop perfectly aligned.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button"
                                class="rounded-full border border-[#c9b39b] px-6 py-3 text-sm font-semibold text-[#1c1b16] transition hover:bg-white/80"
                                @click="plansOpen = true">
                            View plans
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/80 p-4 text-sm shadow-sm ring-1 ring-[#e6d9c7]">
                            <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Avg checkout</div>
                            <div class="text-xl font-semibold">22s</div>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 text-sm shadow-sm ring-1 ring-[#e6d9c7]">
                            <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Stock accuracy</div>
                            <div class="text-xl font-semibold">98%</div>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 text-sm shadow-sm ring-1 ring-[#e6d9c7]">
                            <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Locations live</div>
                            <div class="text-xl font-semibold">3 mins</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="rounded-[32px] bg-[#1c1b16] p-6 text-white shadow-2xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs uppercase tracking-[0.3em] text-[#d5c1aa]">Live sales</div>
                                <div class="text-3xl font-semibold">₱128,540</div>
                                <div class="text-xs text-[#d5c1aa]">Today across 4 cafes</div>
                            </div>
                            <div class="rounded-2xl bg-white/10 px-3 py-2 text-xs">+18% WoW</div>
                        </div>
                        <div class="mt-6 grid gap-3">
                            <div class="rounded-2xl bg-white/10 p-4">
                                <div class="text-xs uppercase tracking-wide text-[#f7d9c8]">Top seller</div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm">Oat latte</span>
                                    <span class="text-sm">₱48,200</span>
                                </div>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4">
                                <div class="text-xs uppercase tracking-wide text-[#d9e5ff]">Inventory alert</div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm">Colombia beans</span>
                                    <span class="text-xs rounded-full bg-[#fbd38d] px-2 py-1 text-[#1c1b16]">Low</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-8 -left-6 hidden w-40 rounded-3xl bg-white/90 p-4 text-xs shadow-xl ring-1 ring-[#e6d9c7] lg:block">
                        <div class="text-[#9b8b7a]">New hire onboarded</div>
                        <div class="mt-2 text-sm font-semibold">Cashier access granted</div>
                    </div>
                </div>
            </div>

            <section class="mt-16 grid gap-6 lg:grid-cols-[0.45fr_0.55fr]">
                <div class="rounded-3xl bg-gradient-to-br from-white/90 to-[#eef6ff] p-6 shadow-sm ring-1 ring-[#d7e6f8]">
                    <div class="text-xs uppercase tracking-[0.3em] text-[#9b8b7a]">What you get</div>
                    <h2 class="mt-3 text-2xl font-semibold">Everything your team needs to move fast.</h2>
                    <p class="mt-3 text-sm text-[#5e5246]">
                        One login for every store. Real-time reporting, smart inventory, and role-based access
                        so every barista works in sync.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#fff4ea] p-5 shadow-sm ring-1 ring-[#e6d9c7]">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">POS that flows</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Tap-to-serve workflows and instant receipts.</p>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#eef8ff] p-5 shadow-sm ring-1 ring-[#d7e6f8]">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Inventory clarity</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Smart alerts and supplier-ready reports.</p>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#f3f0ff] p-5 shadow-sm ring-1 ring-[#ddd2f6]">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Staff and roles</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Assign permissions for every shift.</p>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#edf9f1] p-5 shadow-sm ring-1 ring-[#d5ecd8]">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Analytics</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Track growth with daily insights.</p>
                    </div>
                </div>
            </section>

            <section class="mt-16 rounded-[32px] bg-[#1c1b16] px-8 py-10 text-white">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-[0.3em] text-[#d5c1aa]">Ready to open</div>
                        <h3 class="mt-3 text-2xl font-semibold">Launch your first BrewCloud shop today.</h3>
                        <p class="mt-2 text-sm text-[#d5c1aa]">No credit card required. Cancel anytime.</p>
                    </div>
                    <a href="{{ route('tenant.register') }}" class="rounded-full border border-white/50 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white hover:text-[#1c1b16]">
                        Register shop
                    </a>
                </div>
            </section>
        </main>

        <div x-cloak x-show="plansOpen" class="modal-backdrop" @click="plansOpen = false"></div>
        <div x-cloak x-show="plansOpen" class="modal-panel" role="dialog" aria-modal="true" aria-label="Plans">
            <div class="modal-card" @click.stop>
                <div class="flex items-center justify-between">
                    <div class="modal-title">Plans</div>
                    <button type="button" class="modal-close" @click="plansOpen = false">Close</button>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @php
                        $plans = config('plans', []);
                    @endphp
                    @forelse ($plans as $planKey => $plan)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
                            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $plan['name'] ?? ucfirst($planKey) }}</div>
                            @if (!empty($plan['price']))
                                <div class="mt-2 text-2xl font-semibold">₱{{ number_format($plan['price']) }}</div>
                                <div class="text-xs text-slate-400">per month</div>
                            @else
                                <div class="mt-2 text-xl font-semibold">Custom</div>
                                <div class="text-xs text-slate-400">Contact sales</div>
                            @endif
                            @if (isset($plan['max_users']))
                                <div class="mt-3 text-xs text-slate-500">Up to {{ $plan['max_users'] }} staff</div>
                            @endif
                            @if (!empty($plan['features']))
                                <ul class="mt-3 list-disc pl-5 text-xs text-slate-600">
                                    @foreach ($plan['features'] as $feature)
                                        <li>{{ str_replace('_', ' ', $feature) }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">No plans configured.</div>
                    @endforelse
                </div>
                <div class="mt-6 flex justify-end">
                    <a href="{{ route('tenant.register') }}" class="rounded-full border border-[#c9b39b] px-5 py-2 text-xs font-semibold uppercase tracking-wide text-[#1c1b16] transition hover:bg-[#1c1b16] hover:text-white">
                        Register shop
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>