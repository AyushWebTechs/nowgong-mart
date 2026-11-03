@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <div data-live-refresh="30" hidden></div>
    <h1 class="mb-4 text-xl font-bold">Orders</h1>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-3 lg:grid-cols-6">
        <input class="input lg:col-span-2" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order no., phone or name" aria-label="Search">
        <select class="input" name="status" aria-label="Status">
            <option value="">All statuses</option>
            @foreach (\App\Enums\OrderStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select class="input" name="payment_method" aria-label="Payment method">
            <option value="">All payments</option>
            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(($filters['payment_method'] ?? '') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
        <input class="input" type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date">
        <input class="input" type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date">
        <div class="flex gap-2 sm:col-span-3 lg:col-span-6">
            <button class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-x-auto">
        @include('admin.orders.table', ['orders' => $orders])
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
