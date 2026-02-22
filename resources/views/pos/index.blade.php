<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Point of Sale') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Products</h3>
                        <div class="space-y-3">
                            @foreach ($products as $product)
                                <div class="flex items-center justify-between border rounded-md p-3">
                                    <div>
                                        <div class="font-medium">{{ $product->name }}</div>
                                        <div class="text-sm text-gray-500">{{ number_format($product->price, 2) }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input class="w-16 border rounded px-2 py-1 text-sm" type="number" min="1" value="1" data-qty="{{ $product->id }}" />
                                        <button class="px-3 py-1 bg-gray-900 text-white rounded" data-add="{{ $product->id }}">Add</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">Cart</h3>
                        <div id="cart-items" class="space-y-2 text-sm text-gray-700">
                            <div class="text-gray-500">No items yet.</div>
                        </div>
                        <form class="mt-4" method="POST" action="{{ route('pos.submit') }}" id="pos-submit">
                            @csrf
                            <input type="hidden" name="items" id="pos-items" />
                            <button class="px-4 py-2 bg-emerald-600 text-white rounded">Submit Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const cart = {};
        const cartEl = document.getElementById('cart-items');
        const itemsInput = document.getElementById('pos-items');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function renderCart() {
            const entries = Object.entries(cart);
            if (entries.length === 0) {
                cartEl.innerHTML = '<div class="text-gray-500">No items yet.</div>';
                itemsInput.value = '';
                return;
            }

            cartEl.innerHTML = entries.map(([id, qty]) => {
                return `<div class="flex justify-between"><span>Product #${id}</span><span>x${qty}</span></div>`;
            }).join('');

            itemsInput.value = JSON.stringify(entries.map(([product_id, quantity]) => ({
                product_id: Number(product_id),
                quantity: Number(quantity),
            })));
        }

        document.querySelectorAll('[data-add]').forEach((button) => {
            button.addEventListener('click', async () => {
                const productId = button.getAttribute('data-add');
                const qtyInput = document.querySelector(`[data-qty="${productId}"]`);
                const quantity = Number(qtyInput.value || 1);

                const response = await fetch('{{ route('pos.cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ product_id: Number(productId), quantity }),
                });

                if (response.ok) {
                    cart[productId] = (cart[productId] || 0) + quantity;
                    renderCart();
                }
            });
        });

        renderCart();
    </script>
</x-app-layout>
