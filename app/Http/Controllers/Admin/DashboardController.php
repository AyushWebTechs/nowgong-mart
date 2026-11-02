<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Order::whereDate('placed_at', today())->where('status', '!=', OrderStatus::Cancelled);

        return view('admin.dashboard', [
            'todayCount' => (clone $today)->count(),
            'todaySales' => (clone $today)->sum('total'),
            'statusCounts' => Order::query()
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'upiToVerify' => Payment::with('order')
                ->where('method', PaymentMethod::Upi)
                ->where('status', PaymentStatus::Pending)
                ->whereNotNull('transaction_reference')
                ->whereHas('order', fn ($q) => $q->where('status', '!=', OrderStatus::Cancelled))
                ->latest()
                ->get(),
            'codMismatches' => Payment::with('order')
                ->where('method', PaymentMethod::Cod)
                ->where('status', PaymentStatus::Pending)
                ->whereNotNull('collected_amount')
                ->latest()
                ->get(),
            'lowStock' => Product::lowStock()->where('is_active', true)->orderBy('stock_qty')->take(10)->get(),
            'recentOrders' => Order::latest('placed_at')->take(10)->get(),
        ]);
    }
}
