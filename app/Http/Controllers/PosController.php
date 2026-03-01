<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:use pos')->only(['index', 'addItem', 'removeItem']);
        $this->middleware('permission:create orders')->only(['submit']);
        $this->middleware('permission:process payments')->only(['submit']);
    }

    public function index()
    {
        $products = Product::orderBy('name')->get();
        $cart = $this->normalizeCart(request()->session()->get('pos_cart', []));

        return view('pos.index', compact('products', 'cart'));
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $request->session()->get('pos_cart', []);
        $productId = (string) $data['product_id'];
        $cart[$productId] = ($cart[$productId] ?? 0) + $data['quantity'];
        $request->session()->put('pos_cart', $cart);

        return response()->json(['cart' => $cart]);
    }

    public function removeItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $cart = $request->session()->get('pos_cart', []);
        $productId = (string) $data['product_id'];
        unset($cart[$productId]);
        $request->session()->put('pos_cart', $cart);

        return response()->json(['cart' => $cart]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'cashier_note' => ['nullable', 'string', 'max:500'],
        ]);

        $customerName = isset($validated['customer_name'])
            ? trim((string) $validated['customer_name'])
            : null;

        if ($customerName === '') {
            $customerName = null;
        }

        $cashierNote = isset($validated['cashier_note'])
            ? trim((string) $validated['cashier_note'])
            : null;

        if ($cashierNote === '') {
            $cashierNote = null;
        }

        $items = $request->input('items', []);
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
        if (!is_array($items) || count($items) === 0) {
            $items = $this->normalizeCart($request->session()->get('pos_cart', []));
        }

        if (count($items) === 0) {
            return back()->withErrors(['cart' => 'Cart is empty']);
        }

        $productIds = array_values(array_unique(array_column($items, 'product_id')));
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $total = 0;
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                return back()->withErrors(['cart' => 'Product not found']);
            }
            if ($product->stock < $item['quantity']) {
                return back()->withErrors(['cart' => $product->name . ' is out of stock']);
            }
            $total += $product->price * $item['quantity'];
        }

        $hasCustomerNameColumn = Schema::connection('tenant')->hasColumn('orders', 'customer_name');

        if (!$hasCustomerNameColumn) {
            try {
                Schema::connection('tenant')->table('orders', function (Blueprint $table) {
                    $table->string('customer_name')->nullable()->after('status');
                });

                $hasCustomerNameColumn = true;
            } catch (\Throwable $e) {
                $hasCustomerNameColumn = false;
            }
        }

        $hasCashierNoteColumn = Schema::connection('tenant')->hasColumn('orders', 'cashier_note');

        if (!$hasCashierNoteColumn) {
            try {
                Schema::connection('tenant')->table('orders', function (Blueprint $table) {
                    $table->text('cashier_note')->nullable()->after('status');
                });

                $hasCashierNoteColumn = true;
            } catch (\Throwable $e) {
                $hasCashierNoteColumn = false;
            }
        }

        $order = DB::transaction(function () use ($items, $products, $total, $customerName, $hasCustomerNameColumn, $cashierNote, $hasCashierNoteColumn) {
            $orderPayload = [
                'user_id' => auth()->id(),
                'total' => $total,
                'status' => 'pending',
            ];

            if ($hasCustomerNameColumn) {
                $orderPayload['customer_name'] = $customerName;
            }

            if ($hasCashierNoteColumn) {
                $orderPayload['cashier_note'] = $cashierNote;
            }

            $order = Order::create($orderPayload);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            return $order;
        });

        ActivityLogger::log(
            'order.created',
            'Processed POS order #' . $order->id,
            $order,
            [
                'total' => (float) $order->total,
                'items_count' => count($items),
                'customer_name' => $order->customer_name,
                'cashier_note' => $order->cashier_note,
            ]
        );

        $request->session()->forget('pos_cart');

        return redirect()->route('pos.index', ['subdomain' => request()->route('subdomain')])->with('status', 'Order created');
    }

    private function normalizeCart(array $cart): array
    {
        $items = [];
        foreach ($cart as $productId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $items[] = [
                    'product_id' => (int) $productId,
                    'quantity' => $quantity,
                ];
            }
        }

        return $items;
    }
}
