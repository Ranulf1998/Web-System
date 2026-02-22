<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage brewing orders')->only(['index', 'show', 'update']);
    }

    public function index(): View
    {
        $orders = Order::with(['items.product', 'user'])
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        abort(404);
    }

    public function show(string $subdomain, string $id): View
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    public function edit(string $subdomain, string $id)
    {
        abort(404);
    }

    public function update(Request $request, string $subdomain, string $id): RedirectResponse
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:pending,brewing,done,cancelled'],
        ]);

        $order->update([
            'status' => $data['status'],
        ]);

        ActivityLogger::log(
            'order.status.updated',
            'Updated order #' . $order->id . ' status to ' . $data['status'],
            $order,
            ['status' => $data['status']]
        );

        return redirect()->route('orders.index', ['subdomain' => request()->route('subdomain')])
            ->with('status', 'Order status updated');
    }

    public function destroy(string $subdomain, string $id)
    {
        abort(404);
    }
}