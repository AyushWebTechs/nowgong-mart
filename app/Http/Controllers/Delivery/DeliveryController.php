<?php

namespace App\Http\Controllers\Delivery;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $mine = Order::where('delivery_person_id', $request->user()->id);

        return view('delivery.index', [
            'active' => (clone $mine)
                ->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery])
                ->withCount('items')
                ->oldest('placed_at')
                ->get(),
            'deliveredToday' => (clone $mine)
                ->where('status', OrderStatus::Delivered)
                ->whereDate('delivered_at', today())
                ->latest('delivered_at')
                ->get(),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('deliver', $order);

        return view('delivery.show', ['order' => $order->load('items', 'payment')]);
    }

    public function pickup(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('deliver', $order);

        $this->orders->transition($order, OrderStatus::OutForDelivery, $request->user(), 'Picked up from shop');

        return back()->with('success', 'Marked as picked up. Drive safe!');
    }

    public function delivered(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('deliver', $order);

        $data = $request->validate([
            'collected_amount' => [$order->payment_method === PaymentMethod::Cod ? 'required' : 'nullable', 'decimal:0,2', 'min:0', 'max:99999999'],
        ]);

        $updated = $this->orders->markDelivered($order, $request->user(), isset($data['collected_amount']) ? (string) $data['collected_amount'] : null);

        $message = 'Order delivered.';
        if ($updated->payment_method === PaymentMethod::Cod && $updated->payment_status->value !== 'paid') {
            $message .= ' The cash amount does not match the order total - the shop has been notified.';
        }

        return redirect()->route('delivery.index')->with('success', $message);
    }
}
