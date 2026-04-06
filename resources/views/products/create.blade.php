<x-app-layout>
    @php
        $parseIniBytes = static function (?string $size): int {
            $value = strtolower(trim((string) $size));

            if ($value === '') {
                return 0;
            }

            $unit = substr($value, -1);
            $number = (float) $value;

            return match ($unit) {
                'g' => (int) round($number * 1024 ** 3),
                'm' => (int) round($number * 1024 ** 2),
                'k' => (int) round($number * 1024),
                default => (int) round($number),
            };
        };
        $postMaxBytes = $parseIniBytes(ini_get('post_max_size'));
        $uploadMaxBytes = $parseIniBytes(ini_get('upload_max_filesize'));
        $effectiveMaxBytes = collect([$postMaxBytes, $uploadMaxBytes])->filter(fn (int $bytes) => $bytes > 0)->min() ?? 0;
        $maxUploadSizeLabel = $effectiveMaxBytes > 0
            ? number_format($effectiveMaxBytes / 1024 / 1024, 2) . ' MB'
            : (ini_get('post_max_size') ?: 'N/A');
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (request()->query('upload_error') === 'file_too_large')
                        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            File too large. Please upload a smaller image and try again. Maximum allowed: {{ $maxUploadSizeLabel }}.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Product Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="mt-4">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <!-- Price -->
                        <div class="mt-4">
                            <x-input-label for="price" :value="__('Price (₱)')" />
                            <x-text-input id="price" class="block mt-1 w-full" type="number" step="0.01" name="price" :value="old('price')" required />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>

                        <!-- Stock -->
                        <div class="mt-4">
                            <x-input-label for="stock" :value="__('Initial Stock')" />
                            <x-text-input id="stock" class="block mt-1 w-full" type="number" name="stock" :value="old('stock', 0)" />
                            <x-input-error :messages="$errors->get('stock')" class="mt-2" />
                        </div>

                        <!-- Image (optional) -->
                        <div class="mt-4">
                            <x-input-label for="image" :value="__('Product Image')" />
                            <input id="image" type="file" name="image" class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('Create Product') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
