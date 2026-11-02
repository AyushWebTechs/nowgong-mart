@extends('layouts.app')

@section('title', 'My orders')

@section('content')
    @include('customer.partials.account-tabs')
    <h1 class="mb-4 text-xl font-bold">My orders</h1>

    @if ($orders->isEmpty())
        <div class="card p-8 text-center text-gray-600">
            You have not placed any orders yet. <a href="{{ route('products.index') }}" class="text-brand-700 underline">Start shopping</a>.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="card flex flex-wrap items-center justify-between gap-2 p-4 hover:border-brand-500">
                    <div>
                        <p class="font-semibold">{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-500">{{ $order->placed_at->format('d M Y, h:i A') }} · {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }} · {{ $order->payment_method->label() }}</p>
                    </div>
                    <div class="text-right">
                        <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                        <p class="mt-1 font-bold">{{ money($order->total) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection
