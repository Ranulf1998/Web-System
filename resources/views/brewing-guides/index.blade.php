<x-app-layout>
    <x-slot name="header">
        <div class="dashboard-header">
            <div>
                <h2 class="dashboard-title">How to Brew</h2>
                <p class="dashboard-subtitle">Step-by-step brewing guides for your team</p>
            </div>
            @can('manage users')
                <a href="{{ route('brewing-guides.create') }}" class="brand-button inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold text-white">
                    New Guide
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="dashboard-shell py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($guides->isEmpty())
                <div class="dashboard-panel p-8 text-center">
                    <div class="text-slate-400 mb-3">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <p class="text-slate-600">No brewing guides yet.</p>
                    @can('manage users')
                        <a href="{{ route('brewing-guides.create') }}" class="mt-4 inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Create your first guide
                        </a>
                    @endcan
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($guides as $guide)
                        <a href="{{ route('brewing-guides.show', $guide) }}" class="dashboard-panel p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                            @if ($guide->image_path)
                                <div class="aspect-video w-full overflow-hidden rounded-lg bg-slate-100">
                                    <img src="{{ route('tenant.files.show', ['path' => $guide->image_path]) }}" alt="{{ $guide->title }}" class="h-full w-full object-cover" />
                                </div>
                            @else
                                <div class="aspect-video w-full overflow-hidden rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="mt-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $guide->title }}</h3>
                                    @if ($guide->difficulty)
                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs uppercase tracking-wide text-slate-500">
                                            {{ $guide->difficulty }}
                                        </span>
                                    @endif
                                </div>
                                @if ($guide->description)
                                    <p class="mt-2 line-clamp-2 text-xs text-slate-600">{{ $guide->description }}</p>
                                @endif
                                <div class="mt-3 flex items-center gap-3 text-xs text-slate-400">
                                    @if ($guide->prep_time)
                                        <span>Prep: {{ $guide->prep_time }}m</span>
                                    @endif
                                    @if ($guide->brew_time)
                                        <span>Brew: {{ $guide->brew_time }}m</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $guides->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
