<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Point of Sale') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->has('cart'))
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first('cart') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Products</h3>
                        <div class="space-y-3">
                            @foreach ($products as $product)
                                <div class="flex items-center justify-between border rounded-md p-3">
                                    <div>
                                        <div class="font-medium">{{ $product->name }}</div>
                                        <div class="text-sm text-gray-500">₱{{ number_format($product->price, 2) }} • Stock: {{ $product->stock }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input class="w-16 border rounded px-2 py-1 text-sm" type="number" min="1" value="1" data-qty="{{ $product->id }}" />
                                        <button class="px-3 py-1 bg-gray-900 text-white rounded disabled:bg-gray-300" data-add="{{ $product->id }}" {{ $product->stock < 1 ? 'disabled' : '' }}>Add</button>
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
                        <div class="mt-3 border-t pt-3">
                            <div class="flex items-center justify-between text-sm font-semibold text-gray-800">
                                <span>Total</span>
                                <span id="cart-total">₱0.00</span>
                            </div>
                        </div>
                        <form class="mt-4" method="POST" action="{{ route('pos.submit') }}" id="pos-submit">
                            @csrf
                            <input type="hidden" name="items" id="pos-items" />
                            <button id="submit-order" class="px-4 py-2 bg-emerald-600 text-white rounded disabled:bg-emerald-300" disabled>Submit Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $productsForPos = $products->mapWithKeys(function ($product) {
            return [
                (string) $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                ],
            ];
        });

        $initialCartForPos = collect($cart)->mapWithKeys(function ($item) {
            return [
                (string) $item['product_id'] => (int) $item['quantity'],
            ];
        });
    @endphp

    <script>
        const products = @json($productsForPos);
        const initialCart = @json($initialCartForPos);
        const cart = { ...initialCart };
        const cartEl = document.getElementById('cart-items');
        const itemsInput = document.getElementById('pos-items');
        const totalEl = document.getElementById('cart-total');
        const submitButton = document.getElementById('submit-order');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function renderCart() {
            const entries = Object.entries(cart);
            if (entries.length === 0) {
                cartEl.innerHTML = '<div class="text-gray-500">No items yet.</div>';
                itemsInput.value = '';
                totalEl.textContent = '₱0.00';
                submitButton.disabled = true;
                return;
            }

            let total = 0;

            cartEl.innerHTML = entries.map(([id, qty]) => {
                const product = products[id];
                if (!product) {
                    return '';
                }

                const lineTotal = product.price * qty;
                total += lineTotal;

                return `
                    <div class="flex items-center justify-between border rounded-md px-3 py-2">
                        <div>
                            <div class="font-medium text-gray-900">${product.name}</div>
                            <div class="text-xs text-gray-500">₱${product.price.toFixed(2)} × ${qty}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-gray-800">₱${lineTotal.toFixed(2)}</span>
                            <button type="button" class="text-xs text-red-600 hover:text-red-700" data-remove="${id}">Remove</button>
                        </div>
                    </div>
                `;
            }).join('');

            totalEl.textContent = `₱${total.toFixed(2)}`;
            submitButton.disabled = false;

            cartEl.querySelectorAll('[data-remove]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const productId = button.getAttribute('data-remove');

                    const response = await fetch('{{ route('pos.cart.remove') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ product_id: Number(productId) }),
                    });

                    if (response.ok) {
                        delete cart[productId];
                        renderCart();
                    }
                });
            });

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

                if (!Number.isInteger(quantity) || quantity < 1) {
                    return;
                }

                const product = products[productId];
                const existingQty = cart[productId] || 0;

                if (!product || existingQty + quantity > product.stock) {
                    return;
                }

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
