@extends('layouts.delivery')

@section('title', $order->order_number)

@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentMethod')

@section('content')
    <a href="{{ route('delivery.index') }}" class="text-sm text-brand-700">← All deliveries</a>

    <div class="mt-2 mb-4 flex items-center justify-between">
        <h1 class="text-lg font-bold">{{ $order->order_number }}</h1>
        <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
    </div>

    <section class="card mb-4 space-y-2 p-4">
        <p class="text-lg font-semibold">{{ $order->customer_name }}</p>
        <p class="text-gray-700">{{ $order->fullAddress() }}</p>
        @if ($order->notes)
            <p class="rounded bg-amber-50 p-2 text-sm text-amber-900">Note: {{ $order->notes }}</p>
        @endif
        <div class="grid grid-cols-2 gap-2 pt-2">
            <a href="tel:+91{{ $order->customer_phone }}" class="btn btn-secondary py-3">📞 Call</a>
            <a href="{{ $order->mapsUrl() }}" target="_blank" rel="noopener" class="btn btn-secondary py-3">🗺️ Directions</a>
        </div>
    </section>

    <section class="card mb-4 p-4">
        <h2 class="mb-2 font-bold">Items to hand over</h2>
        <ul class="divide-y text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between py-2"><span>{{ $item->product_name }}</span><span class="font-semibold">{{ qty($item->quantity) }} {{ $item->unit }}</span></li>
            @endforeach
        </ul>
        <div class="mt-3 flex justify-between border-t pt-3 font-bold"><span>Order total</span><span>{{ money($order->total) }}</span></div>
        <p class="mt-1 text-sm">
            @if ($order->payment_method === PaymentMethod::Cod)
                <span class="font-semibold text-amber-700">Cash on Delivery: collect {{ money($order->total) }}</span>
            @else
                UPI payment: {{ $order->payment_status->label() }}
            @endif
        </p>
    </section>

    @if (in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Preparing], true))
        @if ($order->status === OrderStatus::Preparing)
            <form method="POST" action="{{ route('delivery.orders.pickup', $order) }}">
                @csrf
                <button class="btn btn-primary w-full py-4 text-base">📦 Picked up from shop</button>
            </form>
        @else
            <p class="card p-4 text-center text-sm text-gray-600">The shop is getting this order ready. You can pick it up once it is being packed.</p>
        @endif
    @elseif ($order->status === OrderStatus::OutForDelivery)
        <form method="POST" action="{{ route('delivery.orders.delivered', $order) }}" class="card space-y-3 p-4" data-confirm="Mark this order as delivered?">
            @csrf
            @if ($order->payment_method === PaymentMethod::Cod)
                <div>
                    <label class="label" for="collected_amount">Cash collected (₹)</label>
                    <input class="input py-3 text-lg" id="collected_amount" name="collected_amount" inputmode="decimal" value="{{ old('collected_amount', $order->total) }}" required>
                    <p class="mt-1 text-xs text-gray-500">Enter the exact amount you received. Any difference is reported to the shop.</p>
                </div>
            @endif
            <button class="btn btn-primary w-full py-4 text-base">✅ Delivered</button>
        </form>
    @endif
@endsection
