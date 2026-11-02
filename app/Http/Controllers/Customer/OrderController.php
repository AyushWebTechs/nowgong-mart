<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->withCount('items')->latest('placed_at')->paginate(15);

        return view('customer.orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order, StoreSettings $settings): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('customer.orders.show', [
            'order' => $order->load('items', 'payment', 'statusHistory'),
            'settings' => $settings,
        ]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $orders->transition($order, OrderStatus::Cancelled, $request->user(), 'Cancelled by customer');

        return back()->with('success', 'Your order has been cancelled.');
    }

    public function upiReference(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate(
            ['transaction_reference' => ['required', 'regex:/^[A-Za-z0-9]{6,35}$/']],
            ['transaction_reference.regex' => 'UPI reference should be 6-35 letters/numbers.'],
        );

        $orders->submitUpiReference($order, $data['transaction_reference']);

        return back()->with('success', 'Thanks! The shop will verify your UPI payment.');
    }
}
