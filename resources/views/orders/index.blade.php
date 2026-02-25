<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Order Queue') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="{ showModal: false, selectedOrder: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier Note</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">#{{ $order->id }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $order->created_at?->setTimezone('Asia/Manila')?->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $order->user?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $order->items->sum('quantity') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $order->cashier_note ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">₱{{ number_format((float) $order->total, 2) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold {{ $order->status === 'done' ? 'bg-emerald-100 text-emerald-700' : ($order->status === 'brewing' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <button 
                                            @click="selectedOrder = {{ json_encode([
                                                'id' => $order->id,
                                                'created_at' => $order->created_at?->setTimezone('Asia/Manila')?->format('M j, Y g:i A'),
                                                'user' => $order->user?->name ?? 'N/A',
                                                'status' => ucfirst($order->status),
                                                'total' => number_format((float) $order->total, 2),
                                                'cashier_note' => $order->cashier_note,
                                                'items' => $order->items->map(fn($item) => [
                                                    'product_name' => $item->product?->name ?? 'Unknown Product',
                                                    'quantity' => $item->quantity,
                                                    'price' => number_format((float) $item->price, 2),
                                                    'subtotal' => number_format((float) ($item->price * $item->quantity), 2),
                                                ])
                                            ]) }}; showModal = true" 
                                            class="text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                            View
                                        </button>

                                        @if (auth()->user()->can('manage brewing orders') && in_array($order->status, ['pending', 'brewing'], true))
                                            <form method="POST" action="{{ route('orders.update', $order) }}" class="inline ml-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="done">
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-800">Mark Done</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">No orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div x-show="showModal" 
             x-cloak
             @click.away="showModal = false"
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                     @click="showModal = false"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-6 pt-5 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900" x-text="'Order #' + (selectedOrder?.id || '')"></h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- Order Info -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Date & Time:</span>
                                    <p class="font-medium text-gray-900" x-text="selectedOrder?.created_at"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Cashier:</span>
                                    <p class="font-medium text-gray-900" x-text="selectedOrder?.user"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Status:</span>
                                    <p class="font-medium text-gray-900" x-text="selectedOrder?.status"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total:</span>
                                    <p class="font-medium text-gray-900" x-text="'₱' + (selectedOrder?.total || '0.00')"></p>
                                </div>
                            </div>

                            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Cashier Note</div>
                                <div class="mt-1 text-slate-700" x-text="selectedOrder?.cashier_note || 'No note provided.'"></div>
                            </div>

                            <!-- Order Items -->
                            <div class="border-t pt-4">
                                <h4 class="font-semibold text-gray-900 mb-3">Order Items</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <template x-for="item in selectedOrder?.items || []" :key="item.product_name">
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-900" x-text="item.product_name"></td>
                                                    <td class="px-4 py-2 text-sm text-gray-700" x-text="item.quantity"></td>
                                                    <td class="px-4 py-2 text-sm text-gray-700" x-text="'₱' + item.price"></td>
                                                    <td class="px-4 py-2 text-sm text-gray-700" x-text="'₱' + item.subtotal"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="bg-gray-50 px-6 py-3 flex justify-end">
                        <button @click="showModal = false" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
