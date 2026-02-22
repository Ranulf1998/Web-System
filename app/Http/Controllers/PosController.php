<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $order = DB::transaction(function () use ($items, $products, $total) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'total' => $total,
                'status' => 'pending',
            ]);

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
