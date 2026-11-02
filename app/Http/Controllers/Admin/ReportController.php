<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();

        $delivered = Order::where('status', OrderStatus::Delivered)->whereBetween('placed_at', [$from, $to]);

        return view('admin.reports.sales', [
            'from' => $from,
            'to' => $to,
            'totals' => (clone $delivered)
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(delivery_fee),0) as delivery_fees, COALESCE(SUM(total),0) as total')
                ->first(),
            'byMethod' => (clone $delivered)
                ->selectRaw('payment_method, COUNT(*) as orders, SUM(total) as total')
                ->groupBy('payment_method')
                ->get(),
            'daily' => (clone $delivered)
                ->selectRaw('DATE(placed_at) as day, COUNT(*) as orders, SUM(total) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->get(),
            'topProducts' => OrderItem::query()
                ->whereIn('order_id', (clone $delivered)->select('id'))
                ->selectRaw('product_name, unit, SUM(quantity) as qty, SUM(line_total) as revenue')
                ->groupBy('product_name', 'unit')
                ->orderByDesc('revenue')
                ->take(15)
                ->get(),
            'cancelledCount' => Order::where('status', OrderStatus::Cancelled)->whereBetween('placed_at', [$from, $to])->count(),
        ]);
    }
}
