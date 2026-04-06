<x-app-layout>
    @php
        $isOwner = auth()->user()?->hasRole('Owner') ?? false;
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
            {{ __('Products') }}
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

                    @if($isOwner || auth()->user()?->can('manage products'))
                        <a href="{{ route('products.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">Add Product</a>
                    @endif

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($products as $product)
                            <tr>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ $product->name }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">₱{{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">{{ $product->stock }}</td>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    @if($isOwner || auth()->user()?->can('manage products'))
                                        <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline ml-2">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
