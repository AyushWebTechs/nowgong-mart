@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)

@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentStatus')

@section('content')
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-700 hover:underline">← Orders</a>
    <div class="mt-2 mb-4 flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-bold">Order {{ $order->order_number }}</h1>
        <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
        <span class="badge {{ $order->payment_status->badgeClass() }}">{{ $order->payment_method->label() }}: {{ $order->payment_status->label() }}</span>
        <span class="text-sm text-gray-500">Placed {{ $order->placed_at->format('d M Y, h:i A') }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <section class="card overflow-x-auto p-4">
                <h2 class="mb-3 font-bold">Items</h2>
                <table class="table">
                    <thead><tr><th>Item</th><th>SKU</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-xs text-gray-500">{{ $item->sku }}</td>
                                <td class="text-right">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                                <td class="text-right">{{ money($item->unit_price) }}</td>
                                <td class="text-right">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-right">Subtotal</td><td class="text-right">{{ money($order->subtotal) }}</td></tr>
                        <tr><td colspan="4" class="text-right">Delivery fee</td><td class="text-right">{{ money($order->delivery_fee) }}</td></tr>
                        <tr class="font-bold"><td colspan="4" class="text-right">Total</td><td class="text-right">{{ money($order->total) }}</td></tr>
                    </tfoot>
                </table>
            </section>

            <section class="card overflow-x-auto p-4">
                <h2 class="mb-3 font-bold">Payments</h2>
                <table class="table">
                    <thead><tr><th>Method</th><th>Amount</th><th>Status</th><th>Reference / collection</th><th>Reviewed</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($order->payments as $payment)
                            <tr>
                                <td>{{ $payment->method->label() }}</td>
                                <td>{{ money($payment->amount) }}</td>
                                <td><span class="badge {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span></td>
                                <td class="text-xs">
                                    @if ($payment->transaction_reference) UTR <span class="font-mono select-all">{{ $payment->transaction_reference }}</span><br>@endif
                                    @if ($payment->collected_amount !== null)
                                        Collected {{ money($payment->collected_amount) }} by {{ $payment->collector?->name }}
                                        @if ($payment->hasCollectionMismatch()) <span class="font-semibold text-rose-600">(mismatch)</span> @endif
                                    @endif
                                    @if ($payment->note)<br><span class="text-gray-500">{{ $payment->note }}</span>@endif
                                </td>
                                <td class="text-xs">{{ $payment->verifier?->name }}<br>{{ $payment->verified_at?->format('d M, h:i A') }}</td>
                            </tr>
                            @if ($payment->status === PaymentStatus::Pending && $order->status !== OrderStatus::Cancelled && ($payment->method === \App\Enums\PaymentMethod::Upi || $payment->collected_amount !== null))
                                <tr>
                                    <td colspan="5" class="bg-amber-50">
                                        <p class="mb-2 text-xs text-amber-900">
                                            Check your bank/UPI app or the cash received for exactly {{ money($payment->amount) }}{{ $payment->transaction_reference ? ' with reference '.$payment->transaction_reference : '' }} before marking it received.
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="flex gap-2" data-confirm="Confirm you have received this payment?">
                                                @csrf
                                                <input class="input py-1" name="note" placeholder="Note (optional)">
                                                <button class="btn btn-primary btn-sm">Mark received</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="flex gap-2">
                                                @csrf
                                                <input class="input py-1" name="note" placeholder="Reason (required)" required>
                                                <button class="btn btn-danger btn-sm">Not received</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="card p-4">
                <h2 class="mb-3 font-bold">Status history</h2>
                <ol class="space-y-2 text-sm">
                    @foreach ($order->statusHistory as $entry)
                        <li class="flex gap-3">
                            <span class="w-32 shrink-0 text-xs text-gray-500">{{ $entry->created_at->format('d M, h:i A') }}</span>
                            <span>
                                @if ($entry->from_status && $entry->from_status !== $entry->to_status)
                                    {{ $entry->from_status->label() }} → <strong>{{ $entry->to_status->label() }}</strong>
                                @else
                                    <strong>{{ $entry->to_status->label() }}</strong>
                                @endif
                                <span class="text-gray-500">by {{ $entry->user?->name ?? 'system' }}</span>
                                @if ($entry->note) <span class="text-gray-600">· {{ $entry->note }}</span> @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="card space-y-1 p-4 text-sm">
                <h2 class="mb-1 font-bold">Customer</h2>
                <p class="font-semibold">{{ $order->customer_name }}</p>
                <p><a href="tel:+91{{ $order->customer_phone }}" class="text-brand-700 hover:underline">📞 {{ $order->customer_phone }}</a></p>
                <p class="text-gray-600">{{ $order->fullAddress() }}</p>
                @if ($order->notes)
                    <p class="rounded bg-amber-50 p-2 text-amber-900">Note: {{ $order->notes }}</p>
                @endif
                <a class="btn btn-secondary btn-sm mt-2" target="_blank" rel="noopener"
                   href="https://wa.me/91{{ $order->customer_phone }}?text={{ rawurlencode("Hello {$order->customer_name}, your {$settings->get('store_name')} order {$order->order_number} is {$order->status->label()}. Total ".money($order->total).'.') }}">💬 WhatsApp update</a>
            </section>

            <section class="card p-4">
                <h2 class="mb-3 font-bold">Progress</h2>
                @include('partials.order-tracker', ['order' => $order])
            </section>

            <section class="card space-y-3 p-4 text-sm">
                <h2 class="font-bold">Next step</h2>

                {{-- 1. Accept --}}
                @if ($order->status === OrderStatus::Pending)
                    <form method="POST" action="{{ route('admin.orders.confirm', $order) }}">@csrf<button class="btn btn-primary w-full">Accept order</button></form>
                @endif

                {{-- 2. Pack (a rider can be assigned now so they know what's coming) --}}
                @if ($order->status === OrderStatus::Confirmed)
                    <form method="POST" action="{{ route('admin.orders.prepare', $order) }}">@csrf<button class="btn btn-primary w-full">Start packing</button></form>
                    <form method="POST" action="{{ route('admin.orders.assign-delivery', $order) }}" class="space-y-2 border-t pt-3">
                        @csrf
                        <label class="label" for="delivery_person_id">Delivery partner (optional)</label>
                        <select class="input" id="delivery_person_id" name="delivery_person_id" required>
                            <option value="">Choose…</option>
                            @foreach ($deliveryUsers as $user)
                                <option value="{{ $user->id }}" @selected($order->delivery_person_id === $user->id)>{{ $user->name }} ({{ $user->phone }})</option>
                            @endforeach
                        </select>
                        <button class="btn btn-secondary w-full">{{ $order->delivery_person_id ? 'Reassign' : 'Assign' }}</button>
                    </form>
                @endif

                {{-- 3. Send out: hand to a rider (they can also tap "Picked up" themselves) or deliver as the shop --}}
                @if ($order->status === OrderStatus::Preparing)
                    <form method="POST" action="{{ route('admin.orders.dispatch', $order) }}" class="space-y-2">
                        @csrf
                        <label class="label" for="dispatch_rider">Delivered by</label>
                        <select class="input" id="dispatch_rider" name="delivery_person_id">
                            <option value="">The shop (no delivery partner)</option>
                            @foreach ($deliveryUsers as $user)
                                <option value="{{ $user->id }}" @selected($order->delivery_person_id === $user->id)>{{ $user->name }} ({{ $user->phone }})</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary w-full">Send out for delivery</button>
                        <button class="btn btn-secondary w-full" formaction="{{ route('admin.orders.assign-delivery', $order) }}">Only assign. The partner taps "Picked up"</button>
                    </form>
                    @if ($order->deliveryPerson)
                        <p class="text-xs text-gray-500">Assigned to {{ $order->deliveryPerson->name }}. Waiting for pick-up.</p>
                    @endif
                @endif

                {{-- 4. Delivered (by the shop, or on the rider's behalf) --}}
                @if ($order->status === OrderStatus::OutForDelivery)
                    <p>Out with <strong>{{ $order->deliveryPerson?->name ?? 'the shop' }}</strong>.</p>
                    <form method="POST" action="{{ route('admin.orders.deliver', $order) }}" class="space-y-2" data-confirm="Mark this order as delivered?">
                        @csrf
                        @if ($order->payment_method === \App\Enums\PaymentMethod::Cod)
                            <label class="label" for="collected_amount">Cash collected (₹)</label>
                            <input class="input" id="collected_amount" name="collected_amount" inputmode="decimal" value="{{ old('collected_amount', $order->total) }}" required>
                        @endif
                        <button class="btn btn-primary w-full">Mark delivered</button>
                    </form>
                @elseif (! $order->status->isOpen() && $order->deliveryPerson)
                    <p>Delivered by <strong>{{ $order->deliveryPerson->name }}</strong></p>
                @endif

                @if (in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Preparing], true) && $deliveryUsers->isEmpty())
                    <p class="text-xs text-gray-500">No delivery partners yet. <a class="underline" href="{{ route('admin.delivery-users.index') }}">Add one</a> or deliver as the shop.</p>
                @endif

                @if ($order->status->canTransitionTo(OrderStatus::Cancelled))
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="space-y-2 border-t pt-3" data-confirm="Cancel this order and restore its stock?">
                        @csrf
                        <label class="label" for="reason">Cancel reason</label>
                        <input class="input" id="reason" name="reason" required maxlength="255" placeholder="e.g. customer requested">
                        <button class="btn btn-danger w-full">Cancel order</button>
                    </form>
                @endif

                @if (! $order->status->isOpen())
                    <p class="text-gray-500">No further steps. This order is {{ strtolower($order->status->label()) }}{{ $order->delivered_at ? ' on '.$order->delivered_at->format('d M, h:i A') : '' }}.</p>
                @endif
            </section>
        </aside>
    </div>
@endsection
