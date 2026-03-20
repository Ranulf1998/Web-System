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

    <style>
        html {
            scroll-behavior: smooth;
        }

        [x-cloak] {
            display: none !important;
        }

        .blob-float {
            animation: blob-float 8s ease-in-out infinite;
        }

        .blob-float-delay {
            animation: blob-float 10s ease-in-out infinite;
            animation-delay: 1.2s;
        }

        .blob-float-slow {
            animation: blob-float 12s ease-in-out infinite;
            animation-delay: 0.6s;
        }

        .pulse-soft {
            animation: pulse-soft 2.4s ease-in-out infinite;
        }

        .marquee-track {
            animation: marquee 24s linear infinite;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(6px);
            z-index: 60;
        }

        .modal-panel {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            z-index: 70;
            padding: 1.25rem;
            overflow-y: auto;
        }

        .modal-card {
            width: 100%;
            max-width: 54rem;
            max-height: calc(100vh - 2.5rem);
            border-radius: 1.5rem;
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 24px 70px -32px rgba(15, 23, 42, 0.45);
            padding: 1.25rem;
            overflow-y: auto;
            margin: auto 0;
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
        }

        .modal-close {
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #334155;
            transition: all 150ms ease;
        }

        .modal-close:hover {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
            transform: translateY(-1px);
        }

        @media (min-width: 640px) {
            .modal-card {
                padding: 1.5rem;
            }
        }

        @keyframes blob-float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-12px) scale(1.03); }
        }

        @keyframes pulse-soft {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(28, 27, 22, 0.22); }
            50% { transform: scale(1.015); box-shadow: 0 0 0 10px rgba(28, 27, 22, 0); }
        }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#f8f4ee] via-[#f6efe6] via-35% to-[#e8dff6] text-[#1c1b16] min-h-screen">
    <div class="relative overflow-hidden" x-data="{ plansOpen: false }" @keydown.escape.window="plansOpen = false">
        <div class="blob-float pointer-events-none absolute -top-24 -right-20 h-72 w-72 rounded-full bg-[#d9b38c]/30 blur-3xl"></div>
        <div class="blob-float-delay pointer-events-none absolute -top-20 left-1/3 h-64 w-64 rounded-full bg-[#a8d5ba]/20 blur-3xl"></div>
        <div class="blob-float-slow pointer-events-none absolute -bottom-32 -left-10 h-80 w-80 rounded-full bg-[#9f6f4e]/20 blur-3xl"></div>
        <div class="blob-float pointer-events-none absolute -bottom-16 right-1/4 h-72 w-72 rounded-full bg-[#b7c8ff]/20 blur-3xl"></div>

        <header class="sticky top-0 z-40 mx-auto w-full border-b border-white/40 bg-white/55 px-6 py-4 backdrop-blur-xl lg:px-10">
            <nav class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#1c1b16] text-white transition-transform duration-200 hover:scale-105">
                        BC
                    </span>
                    <div>
                        <div class="text-lg font-semibold">BrewCloud</div>
                        <div class="text-xs uppercase tracking-[0.2em] text-[#7d6d5a]">Coffee SaaS</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <a href="{{ url('/super-admin/login') }}"
                       class="rounded-full border border-[#5f4f97] bg-[#ede9ff] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#4a3d82] transition duration-200 hover:-translate-y-0.5 hover:bg-[#5f4f97] hover:text-white">
                        Admin Login
                    </a>
                    <a href="{{ route('tenant.register') }}"
                       class="rounded-full border border-[#1c1b16] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#1c1b16] transition duration-200 hover:-translate-y-0.5 hover:bg-[#1c1b16] hover:text-white">
                        Register shop
                    </a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 pb-16 pt-10 lg:px-10">
            <div class="grid gap-10 lg:items-center">
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-white/80 to-[#f4efff] px-4 py-1 text-xs uppercase tracking-[0.3em] text-[#7d6d5a] ring-1 ring-[#ddd2f6]">
                        New: multi-tenant coffee platform
                    </div>
                    <h1 class="text-4xl font-semibold leading-tight text-[#1c1b16] sm:text-5xl lg:text-6xl">
                        Run every cafe like a flagship store.
                    </h1>
                    <p class="text-base text-[#5e5246] sm:text-lg lg:text-xl">
                        BrewCloud brings POS, inventory, staff, and analytics into a single workspace.
                        Launch new locations in minutes and keep every shop perfectly aligned.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button"
                                class="rounded-full border border-[#c9b39b] px-6 py-3 text-sm font-semibold text-[#1c1b16] transition duration-200 hover:-translate-y-0.5 hover:bg-white/80"
                                @click="plansOpen = true">
                            View plans
                        </button>
                    </div>

                    <div class="relative overflow-hidden rounded-2xl border border-[#e6d9c7] bg-white/70 py-3 shadow-sm">
                        <div class="marquee-track flex w-[200%] items-center gap-3 px-4">
                            @foreach (['Avg checkout: 22s', 'Stock accuracy: 98%', 'Locations launch: 3 mins', 'Live sales tracking', 'Role-based access control', 'Multi-tenant analytics'] as $chip)
                                <span class="rounded-full border border-[#e2d5c5] bg-white px-3 py-1 text-xs font-medium text-[#5e5246]">{{ $chip }}</span>
                            @endforeach
                            @foreach (['Avg checkout: 22s', 'Stock accuracy: 98%', 'Locations launch: 3 mins', 'Live sales tracking', 'Role-based access control', 'Multi-tenant analytics'] as $chip)
                                <span class="rounded-full border border-[#e2d5c5] bg-white px-3 py-1 text-xs font-medium text-[#5e5246]">{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/80 p-4 text-sm shadow-sm ring-1 ring-[#e6d9c7] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                            <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Avg checkout</div>
                            <div class="text-xl font-semibold">22s</div>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 text-sm shadow-sm ring-1 ring-[#e6d9c7] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                            <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Stock accuracy</div>
                            <div class="text-xl font-semibold">98%</div>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 text-sm shadow-sm ring-1 ring-[#e6d9c7] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                            <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Locations live</div>
                            <div class="text-xl font-semibold">3 mins</div>
                        </div>
                    </div>
                </div>

            </div>

            <section class="mt-16 grid gap-6 lg:grid-cols-[0.45fr_0.55fr]">
                <div class="rounded-3xl bg-gradient-to-br from-white/90 to-[#eef6ff] p-6 shadow-sm ring-1 ring-[#d7e6f8] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                    <div class="text-xs uppercase tracking-[0.3em] text-[#9b8b7a]">What you get</div>
                    <h2 class="mt-3 text-2xl font-semibold">Everything your team needs to move fast.</h2>
                    <p class="mt-3 text-sm text-[#5e5246]">
                        One login for every store. Real-time reporting, smart inventory, and role-based access
                        so every barista works in sync.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#fff4ea] p-5 shadow-sm ring-1 ring-[#e6d9c7] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">POS that flows</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Tap-to-serve workflows and instant receipts.</p>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#eef8ff] p-5 shadow-sm ring-1 ring-[#d7e6f8] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Inventory clarity</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Smart alerts and supplier-ready reports.</p>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#f3f0ff] p-5 shadow-sm ring-1 ring-[#ddd2f6] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Staff and roles</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Assign permissions for every shift.</p>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-white/95 to-[#edf9f1] p-5 shadow-sm ring-1 ring-[#d5ecd8] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <div class="text-xs uppercase tracking-wide text-[#9b8b7a]">Analytics</div>
                        <p class="mt-2 text-sm text-[#5e5246]">Track growth with daily insights.</p>
                    </div>
                </div>
            </section>

            <section class="mt-16 rounded-[32px] bg-[#1c1b16] px-8 py-10 text-white transition duration-200 hover:-translate-y-0.5">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-[0.3em] text-[#d5c1aa]">Ready to open</div>
                        <h3 class="mt-3 text-2xl font-semibold">Launch your first BrewCloud shop today.</h3>
                        <p class="mt-2 text-sm text-[#d5c1aa]">No credit card required. Cancel anytime.</p>
                    </div>
                    <a href="{{ route('tenant.register') }}" class="rounded-full border border-white/50 px-6 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-[#1c1b16]">
                        Register shop
                    </a>
                </div>
            </section>
        </main>

        <div x-cloak x-show="plansOpen" x-transition.opacity.duration.250ms class="modal-backdrop" @click="plansOpen = false"></div>
        <div x-cloak x-show="plansOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-3 scale-95" class="modal-panel" role="dialog" aria-modal="true" aria-label="Plans">
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
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
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