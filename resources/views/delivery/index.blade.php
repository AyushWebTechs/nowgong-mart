@extends('layouts.delivery')

@section('title', 'My deliveries')

@push('head')
    <meta http-equiv="refresh" content="60">
@endpush

@section('content')
    <h1 class="mb-3 text-lg font-bold">Assigned to you ({{ $active->count() }})</h1>

    <div class="space-y-3">
        @forelse ($active as $order)
            <a href="{{ route('delivery.orders.show', $order) }}" class="card block p-4 active:bg-gray-50">
                <div class="flex items-center justify-between">
                    <span class="font-bold">{{ $order->order_number }}</span>
                    <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                </div>
                <p class="mt-1 text-sm font-semibold">{{ $order->customer_name }}</p>
                <p class="text-sm text-gray-600">{{ $order->fullAddress() }}</p>
                <p class="mt-1 text-sm">
                    {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }} ·
                    @if ($order->payment_method === \App\Enums\PaymentMethod::Cod)
                        <strong class="text-amber-700">Collect {{ money($order->total) }} cash</strong>
                    @else
                        UPI ({{ $order->payment_status->label() }})
                    @endif
                </p>
            </a>
        @empty
            <p class="card p-6 text-center text-gray-500">No deliveries assigned right now.</p>
        @endforelse
    </div>

    @if ($deliveredToday->isNotEmpty())
        <h2 class="mt-6 mb-2 font-bold">Delivered today ({{ $deliveredToday->count() }})</h2>
        <div class="card divide-y text-sm">
            @foreach ($deliveredToday as $order)
                <div class="flex justify-between p-3">
                    <span>{{ $order->order_number }} · {{ $order->delivered_at->format('h:i A') }}</span>
                    <span>{{ money($order->total) }}</span>
                </div>
            @endforeach
        </div>
    @endif
@endsection
