<x-app-layout>
    <x-slot name="header">
        <div class="dashboard-header">
            <div>
                <h2 class="dashboard-title">{{ $brewingGuide->title }}</h2>
                @if ($brewingGuide->difficulty)
                    <p class="dashboard-subtitle">Difficulty: {{ ucfirst($brewingGuide->difficulty) }}</p>
                @endif
            </div>
            @can('manage users')
                <div class="flex items-center gap-2">
                    <a href="{{ route('brewing-guides.edit', $brewingGuide) }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('brewing-guides.destroy', $brewingGuide) }}" onsubmit="return confirm('Are you sure you want to delete this guide?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded-full border border-rose-300 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                            Delete
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="dashboard-shell py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="dashboard-panel overflow-hidden">
                @if ($brewingGuide->image_path)
                    <div class="aspect-video w-full overflow-hidden bg-slate-100">
                        <img src="{{ route('tenant.files.show', ['path' => $brewingGuide->image_path]) }}" alt="{{ $brewingGuide->title }}" class="h-full w-full object-cover" />
                    </div>
                @endif

                <div class="p-6">
                    @if ($brewingGuide->description)
                        <div class="mb-6">
                            <p class="text-slate-700">{{ $brewingGuide->description }}</p>
                        </div>
                    @endif

                    <div class="mb-6 flex flex-wrap gap-4 text-sm">
                        @if ($brewingGuide->prep_time)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Prep time</div>
                                <div class="font-semibold">{{ $brewingGuide->prep_time }} minutes</div>
                            </div>
                        @endif
                        @if ($brewingGuide->brew_time)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Brew time</div>
                                <div class="font-semibold">{{ $brewingGuide->brew_time }} minutes</div>
                            </div>
                        @endif
                    </div>

                    @if (!empty($brewingGuide->ingredients))
                        <div class="mb-6">
                            <div class="dashboard-section-title mb-3">Ingredients</div>
                            <ul class="list-disc pl-5 text-sm text-slate-600">
                                @foreach ($brewingGuide->ingredients as $ingredient)
                                    <li>{{ $ingredient }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (!empty($brewingGuide->steps))
                        <div>
                            <div class="dashboard-section-title mb-3">Steps</div>
                            <ol class="space-y-3">
                                @foreach ($brewingGuide->steps as $index => $step)
                                    <li class="flex gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[color:var(--brand-primary)] text-xs font-semibold text-white">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="flex-1 text-sm text-slate-700">{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('brewing-guides.index') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-900">
                    ← Back to guides
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
