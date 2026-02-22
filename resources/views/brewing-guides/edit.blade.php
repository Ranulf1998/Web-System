<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="dashboard-title">Edit Brewing Guide</h2>
            <p class="dashboard-subtitle">{{ $brewingGuide->title }}</p>
        </div>
    </x-slot>

    <div class="dashboard-shell py-8" x-data="{
        ingredients: {{ json_encode(old('ingredients', $brewingGuide->ingredients ?? [''])) }},
        steps: {{ json_encode(old('steps', $brewingGuide->steps ?? [''])) }}
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="dashboard-panel p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('brewing-guides.update', $brewingGuide) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="title" class="dashboard-section-title">Title *</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $brewingGuide->title) }}" required
                               class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]" />
                    </div>

                    <div>
                        <label for="description" class="dashboard-section-title">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]">{{ old('description', $brewingGuide->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="prep_time" class="dashboard-section-title">Prep time (min)</label>
                            <input id="prep_time" name="prep_time" type="number" min="0" value="{{ old('prep_time', $brewingGuide->prep_time) }}"
                                   class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]" />
                        </div>
                        <div>
                            <label for="brew_time" class="dashboard-section-title">Brew time (min)</label>
                            <input id="brew_time" name="brew_time" type="number" min="0" value="{{ old('brew_time', $brewingGuide->brew_time) }}"
                                   class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]" />
                        </div>
                        <div>
                            <label for="difficulty" class="dashboard-section-title">Difficulty</label>
                            <select id="difficulty" name="difficulty"
                                    class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]">
                                <option value="easy" {{ old('difficulty', $brewingGuide->difficulty) === 'easy' ? 'selected' : '' }}>Easy</option>
                                <option value="medium" {{ old('difficulty', $brewingGuide->difficulty) === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="hard" {{ old('difficulty', $brewingGuide->difficulty) === 'hard' ? 'selected' : '' }}>Hard</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="dashboard-section-title">Image</label>
                        @if ($brewingGuide->image_path)
                            <div class="mt-2 mb-3">
                                <img src="{{ route('tenant.files.show', ['path' => $brewingGuide->image_path]) }}" alt="Current image" class="h-32 w-auto rounded-lg border border-slate-200" />
                                <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300" />
                                    Remove current image
                                </label>
                            </div>
                        @endif
                        <input type="file" name="image" accept="image/*"
                               class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="dashboard-section-title">Ingredients</label>
                            <button type="button" @click="ingredients.push('')" class="text-xs font-semibold text-[color:var(--brand-primary)] hover:underline">
                                + Add ingredient
                            </button>
                        </div>
                        <div class="mt-2 space-y-2">
                            <template x-for="(ingredient, index) in ingredients" :key="index">
                                <div class="flex gap-2">
                                    <input type="text" :name="'ingredients[' + index + ']'" x-model="ingredients[index]"
                                           placeholder="e.g., 20g coffee beans"
                                           class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]" />
                                    <button type="button" @click="ingredients.splice(index, 1)" x-show="ingredients.length > 1"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="dashboard-section-title">Steps *</label>
                            <button type="button" @click="steps.push('')" class="text-xs font-semibold text-[color:var(--brand-primary)] hover:underline">
                                + Add step
                            </button>
                        </div>
                        <div class="mt-2 space-y-2">
                            <template x-for="(step, index) in steps" :key="index">
                                <div class="flex gap-2">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600" x-text="index + 1"></span>
                                    <textarea :name="'steps[' + index + ']'" x-model="steps[index]" rows="2" required
                                              placeholder="Describe this step..."
                                              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[color:var(--brand-primary)] focus:outline-none focus:ring-1 focus:ring-[color:var(--brand-primary)]"></textarea>
                                    <button type="button" @click="steps.splice(index, 1)" x-show="steps.length > 1"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Update Guide</x-primary-button>
                        <a href="{{ route('brewing-guides.show', $brewingGuide) }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
