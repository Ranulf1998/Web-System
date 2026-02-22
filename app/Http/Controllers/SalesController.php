<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view reports');
    }

    public function index(Request $request): View
    {
        abort_unless(tenant()->canUseFeature('sales_reports'), 403, 'Sales reports are not available on your current plan.');
        
        $period = $request->input('period', 'all');
        
        $query = Order::with(['items.product', 'user']);

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereYear('created_at', now()->year)
                      ->whereMonth('created_at', now()->month);
                break;
        }

        $orders = $query->latest()
            ->paginate(20)
            ->appends(['period' => $period]);

        // Calculate summary stats for the filtered period
        $summaryQuery = Order::query();
        switch ($period) {
            case 'today':
                $summaryQuery->whereDate('created_at', today());
                break;
            case 'week':
                $summaryQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $summaryQuery->whereYear('created_at', now()->year)
                             ->whereMonth('created_at', now()->month);
                break;
        }

        $totalSales = $summaryQuery->sum('total');
        $totalOrders = $summaryQuery->count();

        // Find most purchased product for the period
        $topProductQuery = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->whereIn('order_id', function ($query) use ($period) {
                $query->select('id')
                    ->from('orders')
                    ->where('tenant_id', tenant()->id);
                
                switch ($period) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereYear('created_at', now()->year)
                              ->whereMonth('created_at', now()->month);
                        break;
                }
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->first();

        $topProduct = $topProductQuery ? $topProductQuery->product?->name : 'N/A';
        $topProductQty = $topProductQuery ? $topProductQuery->total_quantity : 0;

        return view('sales.index', compact('orders', 'period', 'totalSales', 'totalOrders', 'topProduct', 'topProductQty'));
    }
}
