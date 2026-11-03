<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private StoreSettings $settings,
    ) {}

    /**
     * @param  array{recipient_name: string, phone: string, address_line: string, landmark: ?string, locality: string, pincode: string}  $address
     */
    public function place(User $customer, Cart $cart, array $address, PaymentMethod $method, ?string $upiReference = null, ?string $notes = null): Order
    {
        if (! $this->settings->bool('accepting_orders')) {
            throw ValidationException::withMessages(['cart' => 'The shop is not accepting online orders right now. Please try again later.']);
        }

        $pincodes = $this->settings->servicePincodes();
        if ($pincodes && ! in_array($address['pincode'], $pincodes, true)) {
            throw ValidationException::withMessages(['pincode' => 'Sorry, we do not deliver to this pincode yet.']);
        }

        return DB::transaction(function () use ($customer, $cart, $address, $method, $upiReference, $notes) {
            $items = $cart->items()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->load('category')
                ->keyBy('id');

            $subtotal = '0.00';
            $lines = [];

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if (! $product || ! $product->is_active || ! $product->category?->is_active) {
                    throw ValidationException::withMessages(['cart' => ($product->name ?? 'An item').' is no longer available. Please update your cart.']);
                }

                if (! $product->hasStockFor($item->quantity)) {
                    throw ValidationException::withMessages(['cart' => 'Only '.Money::qty($product->stock_qty)." {$product->unit} of {$product->name} left. Please update your cart."]);
                }

                $lineTotal = Money::lineTotal($product->price, $item->quantity);
                $subtotal = Money::add($subtotal, $lineTotal);
                $lines[] = [$product, $item->quantity, $lineTotal];
            }

            $minOrder = $this->settings->money('min_order_value');
            if (bccomp($subtotal, $minOrder, 2) < 0) {
                throw ValidationException::withMessages(['cart' => 'Minimum order value is '.Money::format($minOrder).'.']);
            }

            $deliveryFee = $this->settings->deliveryFeeFor($subtotal);
            $total = Money::add($subtotal, $deliveryFee);

            $order = Order::create([
                'user_id' => $customer->id,
                'order_number' => $this->newOrderNumber(),
                'status' => OrderStatus::Pending,
                'payment_method' => $method,
                'payment_status' => PaymentStatus::Pending,
                'customer_name' => $address['recipient_name'],
                'customer_phone' => $address['phone'],
                'address_line' => $address['address_line'],
                'landmark' => $address['landmark'] ?? null,
                'locality' => $address['locality'],
                'pincode' => $address['pincode'],
                'subtotal' => $subtotal,
                'discount' => '0.00',
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'notes' => $notes,
                'placed_at' => now(),
            ]);

            foreach ($lines as [$product, $quantity, $lineTotal]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);

                if ($product->track_stock) {
                    $this->inventory->apply($product, bcmul($quantity, '-1', 3), MovementType::OnlineOrder, $customer, $order, "Order {$order->order_number}");
                }
            }

            $order->payments()->create([
                'method' => $method,
                'amount' => $total,
                'status' => PaymentStatus::Pending,
                'transaction_reference' => $method === PaymentMethod::Upi ? $upiReference : null,
            ]);

            $this->logStatus($order, null, OrderStatus::Pending, $customer, 'Order placed');

            $cart->items()->delete();

            return $order;
        });
    }

    public function transition(Order $order, OrderStatus $to, User $actor, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $note) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages(['status' => "An order that is {$from->label()} cannot be moved to {$to->label()}."]);
            }

            $this->authorizeTransition($locked, $to, $actor);

            // Without an assigned rider the shop itself takes the order out (admin only, see authorizeTransition).
            $locked->status = $to;

            if ($to === OrderStatus::Delivered) {
                $locked->delivered_at = now();
            }

            if ($to === OrderStatus::Cancelled) {
                $locked->cancelled_at = now();
                $this->inventory->restoreForOrder($locked, $actor);

                if ($locked->payment_status === PaymentStatus::Pending) {
                    $locked->payment_status = PaymentStatus::Failed;
                    $locked->payments()->where('status', PaymentStatus::Pending)->update([
                        'status' => PaymentStatus::Failed,
                        'note' => 'Order cancelled',
                    ]);
                }
            }

            $locked->save();
            $this->logStatus($locked, $from, $to, $actor, $note);

            return $locked;
        });
    }

    public function assignDelivery(Order $order, User $deliveryPerson, User $actor): Order
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException('Only the store admin can assign deliveries.');
        }

        if (! $deliveryPerson->isDelivery() || ! $deliveryPerson->is_active) {
            throw ValidationException::withMessages(['delivery_person_id' => 'Choose an active delivery person.']);
        }

        return DB::transaction(function () use ($order, $deliveryPerson, $actor) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [OrderStatus::Confirmed, OrderStatus::Preparing], true)) {
                throw ValidationException::withMessages(['delivery_person_id' => 'Only confirmed or preparing orders can be assigned.']);
            }

            $locked->delivery_person_id = $deliveryPerson->id;
            $locked->save();
            $this->logStatus($locked, $locked->status, $locked->status, $actor, "Assigned to {$deliveryPerson->name}");

            return $locked;
        });
    }

    /** Admin sends the order out: with a rider (assigned now if chosen) or delivered by the shop itself. */
    public function dispatch(Order $order, User $actor, ?User $rider = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $rider) {
            if ($rider && $rider->id !== $order->delivery_person_id) {
                $order = $this->assignDelivery($order, $rider, $actor);
            }

            $name = $rider?->name ?? $order->deliveryPerson?->name;

            return $this->transition($order, OrderStatus::OutForDelivery, $actor, $name ? "Out with {$name}" : 'Out for delivery by the shop');
        });
    }

    public function markDelivered(Order $order, User $actor, ?string $collectedAmount = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $collectedAmount) {
            $locked = $this->transition($order, OrderStatus::Delivered, $actor, $actor->isAdmin() ? 'Marked delivered by the shop' : null);

            if ($locked->payment_method === PaymentMethod::Cod) {
                $collected = bcadd($collectedAmount ?? '0', '0', 2);
                $payment = $locked->payments()->latest('id')->lockForUpdate()->firstOrFail();
                $mismatch = bccomp($collected, $payment->amount, 2) !== 0;

                $payment->update([
                    'collected_amount' => $collected,
                    'collected_by' => $actor->id,
                    'status' => $mismatch ? PaymentStatus::Pending : PaymentStatus::Paid,
                    'note' => $mismatch ? 'COD collected '.Money::format($collected).' vs due '.Money::format($payment->amount).' - admin to review' : null,
                ]);

                $locked->update(['payment_status' => $payment->status]);
            }

            return $locked;
        });
    }

    public function reviewPayment(Payment $payment, User $actor, bool $approve, ?string $note = null): Payment
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException('Only the store admin can verify payments.');
        }

        return DB::transaction(function () use ($payment, $actor, $approve, $note) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages(['payment' => 'This payment has already been reviewed.']);
            }

            $locked->update([
                'status' => $approve ? PaymentStatus::Paid : PaymentStatus::Failed,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'note' => $note ?: $locked->note,
            ]);

            $locked->order->update(['payment_status' => $locked->status]);

            return $locked;
        });
    }

    public function submitUpiReference(Order $order, string $reference): void
    {
        DB::transaction(function () use ($order, $reference) {
            $payment = $order->payments()->latest('id')->lockForUpdate()->firstOrFail();

            if ($payment->method !== PaymentMethod::Upi || $payment->status !== PaymentStatus::Pending || ! $order->status->isOpen()) {
                throw ValidationException::withMessages(['transaction_reference' => 'This payment can no longer be updated.']);
            }

            $payment->update(['transaction_reference' => $reference]);
        });
    }

    private function authorizeTransition(Order $order, OrderStatus $to, User $actor): void
    {
        $allowed = match ($actor->role) {
            // The owner can run the whole flow, including dispatch and delivery when there is no rider.
            UserRole::Admin => true,
            UserRole::Delivery => in_array($to, [OrderStatus::OutForDelivery, OrderStatus::Delivered], true)
                && $order->delivery_person_id === $actor->id,
            UserRole::Customer => $to === OrderStatus::Cancelled && $order->user_id === $actor->id,
        };

        if (! $allowed) {
            throw new AuthorizationException('You are not allowed to make this change.');
        }

        if ($actor->isCustomer() && ($order->status !== OrderStatus::Pending || ! $this->settings->bool('customer_cancel_allowed'))) {
            throw ValidationException::withMessages(['status' => 'This order can no longer be cancelled online. Please call or WhatsApp the shop.']);
        }
    }

    private function logStatus(Order $order, ?OrderStatus $from, OrderStatus $to, ?User $actor, ?string $note): void
    {
        $order->statusHistory()->create([
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    private function newOrderNumber(): string
    {
        do {
            $number = 'NM'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
