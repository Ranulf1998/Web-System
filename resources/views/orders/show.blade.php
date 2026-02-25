<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Order Details') }} #{{ $order->id }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-sm text-gray-500">Created</div>
                        <div class="font-medium text-gray-900">{{ $order->created_at?->setTimezone('Asia/Manila')?->format('M j, Y g:i A') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="font-medium text-gray-900">{{ ucfirst($order->status) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Cashier</div>
                        <div class="font-medium text-gray-900">{{ $order->user?->name ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Cashier Note</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $order->cashier_note ?: 'No note provided.' }}</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item->product?->name ?? 'Deleted product' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">₱{{ number_format((float) $item->price, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">₱{{ number_format((float) ($item->price * $item->quantity), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t pt-4">
                    <a href="{{ route('orders.index') }}" class="text-sm text-gray-600 hover:text-gray-800">← Back to queue</a>
                    <div class="flex items-center gap-3">
                        <div class="font-semibold text-gray-900">Total: ₱{{ number_format((float) $order->total, 2) }}</div>
                        @if (auth()->user()->can('manage brewing orders') && in_array($order->status, ['pending', 'brewing'], true))
                            <form method="POST" action="{{ route('orders.update', $order) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="done">
                                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded">Confirm Done</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
