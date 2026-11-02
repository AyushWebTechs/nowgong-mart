@extends('layouts.admin')

@section('title', 'Dashboard')

@push('head')
    <meta http-equiv="refresh" content="60">
@endpush

@section('content')
    @use('App\Enums\OrderStatus')

    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-6">
        <div class="card p-4 md:col-span-2">
            <p class="text-xs text-gray-500 uppercase">Today</p>
            <p class="text-2xl font-extrabold">{{ money($todaySales) }}</p>
            <p class="text-sm text-gray-600">{{ $todayCount }} {{ Str::plural('order', $todayCount) }}</p>
        </div>
        @foreach ([OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery] as $status)
            <a href="{{ route('admin.orders.index', ['status' => $status->value]) }}" class="card p-4 hover:border-brand-500">
                <p class="text-xs text-gray-500 uppercase">{{ $status->label() }}</p>
                <p class="text-2xl font-extrabold {{ $status === OrderStatus::Pending && ($statusCounts[$status->value] ?? 0) ? 'text-amber-600' : '' }}">{{ $statusCounts[$status->value] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <p class="mb-4 text-xs text-gray-500">This page refreshes every minute to show new orders.</p>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card p-4">
            <h2 class="mb-3 font-bold">UPI payments to verify</h2>
            @forelse ($upiToVerify as $payment)
                <div class="flex items-center justify-between border-b py-2 text-sm last:border-0">
                    <div>
                        <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-semibold text-brand-700 hover:underline">{{ $payment->order->order_number }}</a>
                        <p class="text-xs text-gray-500">Ref {{ $payment->transaction_reference }} · {{ money($payment->amount) }}</p>
                    </div>
                    <a href="{{ route('admin.orders.show', $payment->order) }}" class="btn btn-secondary btn-sm">Review</a>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nothing to verify.</p>
            @endforelse
        </section>

        <section class="card p-4">
            <h2 class="mb-3 font-bold">Low stock</h2>
            @forelse ($lowStock as $product)
                <div class="flex items-center justify-between border-b py-2 text-sm last:border-0">
                    <span>{{ $product->name }}</span>
                    <span class="font-semibold {{ (float) $product->stock_qty <= 0 ? 'text-rose-600' : 'text-amber-600' }}">{{ qty($product->stock_qty) }} {{ $product->unit }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">All products are above their low-stock threshold.</p>
            @endforelse
            <a href="{{ route('admin.inventory.index', ['low' => 1]) }}" class="mt-2 inline-block text-sm text-brand-700 hover:underline">Manage stock →</a>
        </section>

        @if ($codMismatches->isNotEmpty())
            <section class="card border-rose-300 p-4 lg:col-span-2">
                <h2 class="mb-3 font-bold text-rose-700">COD cash mismatches</h2>
                @foreach ($codMismatches as $payment)
                    <div class="flex items-center justify-between border-b py-2 text-sm last:border-0">
                        <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-semibold text-brand-700 hover:underline">{{ $payment->order->order_number }}</a>
                        <span>Due {{ money($payment->amount) }} · collected {{ money($payment->collected_amount) }}</span>
                    </div>
                @endforeach
            </section>
        @endif

        <section class="card overflow-x-auto p-4 lg:col-span-2">
            <h2 class="mb-3 font-bold">Recent orders</h2>
            @include('admin.orders.table', ['orders' => $recentOrders])
        </section>
    </div>
@endsection
