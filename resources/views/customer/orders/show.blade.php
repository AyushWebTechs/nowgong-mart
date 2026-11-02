@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentMethod')
@use('App\Enums\PaymentStatus')

@section('content')
    @php
        $steps = OrderStatus::progression();
        $currentIndex = array_search($order->status, $steps, true);
    @endphp

    <a href="{{ route('orders.index') }}" class="text-sm text-brand-700 hover:underline">← All orders</a>
    <div class="mt-2 mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold">Order {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Placed {{ $order->placed_at->format('d M Y, h:i A') }}</p>
        </div>
        <span class="badge {{ $order->status->badgeClass() }} text-sm">{{ $order->status->label() }}</span>
    </div>

    @if ($order->status === OrderStatus::Cancelled)
        <div class="card mb-4 border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">This order was cancelled on {{ $order->cancelled_at?->format('d M Y, h:i A') }}.</div>
    @else
        <ol class="card mb-4 grid grid-cols-5 gap-1 p-4 text-center text-[11px] sm:text-xs" aria-label="Order progress">
            @foreach ($steps as $index => $step)
                <li class="flex flex-col items-center gap-1">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $index <= $currentIndex ? 'bg-brand-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $index < $currentIndex ? '✓' : $index + 1 }}</span>
                    <span class="{{ $index <= $currentIndex ? 'font-semibold text-gray-900' : 'text-gray-500' }}">{{ $step->label() }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="card p-4 lg:col-span-2">
            <h2 class="mb-3 font-bold">Items</h2>
            <table class="table">
                <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-right">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                            <td class="text-right">{{ money($item->unit_price) }}</td>
                            <td class="text-right">{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="text-sm">
                    <tr><td colspan="3" class="text-right">Subtotal</td><td class="text-right">{{ money($order->subtotal) }}</td></tr>
                    <tr><td colspan="3" class="text-right">Delivery fee</td><td class="text-right">{{ money($order->delivery_fee) }}</td></tr>
                    <tr class="font-bold"><td colspan="3" class="text-right">Total</td><td class="text-right">{{ money($order->total) }}</td></tr>
                </tfoot>
            </table>
        </section>

        <aside class="space-y-4">
            <section class="card p-4 text-sm">
                <h2 class="mb-2 font-bold">Delivery to</h2>
                <p class="font-semibold">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                <p class="text-gray-600">{{ $order->fullAddress() }}</p>
                @if ($order->notes)
                    <p class="mt-2 text-gray-600"><span class="font-semibold">Note:</span> {{ $order->notes }}</p>
                @endif
            </section>

            <section class="card p-4 text-sm">
                <h2 class="mb-2 font-bold">Payment</h2>
                <p>{{ $order->payment_method->label() }} · <span class="badge {{ $order->payment_status->badgeClass() }}">{{ $order->payment_status->label() }}</span></p>
                @if ($order->payment_method === PaymentMethod::Upi && $order->payment_status === PaymentStatus::Pending && $order->status->isOpen())
                    @if ($settings->get('upi_id'))
                        <p class="mt-2">Pay {{ money($order->total) }} to <strong class="select-all">{{ $settings->get('upi_id') }}</strong></p>
                        @if ($settings->get('upi_qr_path'))
                            <img src="{{ Storage::disk('public')->url($settings->get('upi_qr_path')) }}" alt="Shop UPI QR code" class="mt-2 h-36 w-36 rounded border bg-white">
                        @endif
                    @endif
                    <form method="POST" action="{{ route('orders.upi-reference', $order) }}" class="mt-3 space-y-2">
                        @csrf
                        <label class="label" for="transaction_reference">UPI reference / UTR</label>
                        <input class="input" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference', $order->payment?->transaction_reference) }}" autocomplete="off">
                        <button class="btn btn-secondary btn-sm">{{ $order->payment?->transaction_reference ? 'Update reference' : 'Submit reference' }}</button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">The shop verifies every UPI payment in its bank app before marking it paid.</p>
                @endif
            </section>

            <section class="card space-y-2 p-4 text-sm">
                @if ($settings->get('whatsapp_number'))
                    <a class="btn btn-secondary w-full" target="_blank" rel="noopener"
                       href="https://wa.me/91{{ $settings->get('whatsapp_number') }}?text={{ rawurlencode("Hi, I have a question about my order {$order->order_number}.") }}">💬 Ask the shop on WhatsApp</a>
                @endif
                @if ($settings->get('store_phone'))
                    <a class="btn btn-secondary w-full" href="tel:+91{{ $settings->get('store_phone') }}">📞 Call the shop</a>
                @endif
                @if ($order->status === OrderStatus::Pending && $settings->bool('customer_cancel_allowed'))
                    <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel this order?">
                        @csrf
                        <button class="btn btn-danger w-full">Cancel order</button>
                    </form>
                    <p class="text-xs text-gray-500">You can cancel until the shop confirms your order.</p>
                @endif
            </section>
        </aside>
    </div>
@endsection
