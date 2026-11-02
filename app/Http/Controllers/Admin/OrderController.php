<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $orders = Order::with('deliveryPerson')
            ->withCount('items')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['payment_method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('placed_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('placed_at', '<=', $d))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")))
            ->latest('placed_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', ['orders' => $orders, 'filters' => $filters]);
    }

    public function show(Order $order, StoreSettings $settings): View
    {
        return view('admin.orders.show', [
            'order' => $order->load('items', 'payments.verifier', 'payments.collector', 'statusHistory.user', 'customer', 'deliveryPerson'),
            'deliveryUsers' => User::where('role', UserRole::Delivery)->where('is_active', true)->orderBy('name')->get(),
            'settings' => $settings,
        ]);
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $this->orders->transition($order, OrderStatus::Confirmed, $request->user());

        return back()->with('success', "Order {$order->order_number} confirmed.");
    }

    public function prepare(Request $request, Order $order): RedirectResponse
    {
        $this->orders->transition($order, OrderStatus::Preparing, $request->user());

        return back()->with('success', "Order {$order->order_number} is being prepared.");
    }

    public function assignDelivery(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['delivery_person_id' => ['required', 'integer']]);

        $deliveryPerson = User::findOrFail($data['delivery_person_id']);
        $this->orders->assignDelivery($order, $deliveryPerson, $request->user());

        return back()->with('success', "Assigned to {$deliveryPerson->name}.");
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $updated = $this->orders->transition($order, OrderStatus::Cancelled, $request->user(), $data['reason']);

        $message = "Order {$order->order_number} cancelled and stock restored.";
        if ($updated->payment_status->value === 'paid') {
            $message .= ' This order was already paid - refund the customer manually.';
        }

        return back()->with('success', $message);
    }
}
