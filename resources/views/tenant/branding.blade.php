<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="dashboard-title">Branding</h2>
            <p class="dashboard-subtitle">Customize your logo and colors.</p>
        </div>
    </x-slot>

    @php
        $logoPath = $branding['logo_path'] ?? null;
        $primaryColor = $branding['primary'] ?? '#0f766e';
        $accentColor = $branding['accent'] ?? '#f59e0b';
        $backgroundColor = $branding['background'] ?? '#f3f4f6';
    @endphp

    <div class="dashboard-shell py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="dashboard-panel p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <div class="dashboard-section-title">Logo</div>
                        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="h-16 w-16 rounded-xl border border-slate-200 bg-slate-50 p-2">
                                @if ($logoPath)
                                    <img src="{{ route('tenant.files.show', ['path' => $logoPath]) }}" alt="Tenant logo" class="h-full w-full object-contain" />
                                @else
                                    <x-application-logo class="h-full w-full fill-current text-slate-400" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                <p class="mt-2 text-xs text-slate-500">PNG, JPG, WEBP, or SVG. Max 2MB.</p>
                                @if ($logoPath)
                                    <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300" />
                                        Remove current logo
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="dashboard-section-title" for="primary_color">Primary Color</label>
                            <input id="primary_color" name="primary_color" type="color" value="{{ $primaryColor }}" class="mt-2 h-12 w-full rounded-lg border border-slate-200" />
                        </div>
                        <div>
                            <label class="dashboard-section-title" for="accent_color">Accent Color</label>
                            <input id="accent_color" name="accent_color" type="color" value="{{ $accentColor }}" class="mt-2 h-12 w-full rounded-lg border border-slate-200" />
                        </div>
                        <div>
                            <label class="dashboard-section-title" for="background_color">Background</label>
                            <input id="background_color" name="background_color" type="color" value="{{ $backgroundColor }}" class="mt-2 h-12 w-full rounded-lg border border-slate-200" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Save Branding</x-primary-button>
                        <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>