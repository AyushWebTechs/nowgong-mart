@extends('layouts.admin')

@section('title', 'Sales report')

@section('content')
    <h1 class="mb-4 text-xl font-bold">Sales report</h1>

    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div><label class="label" for="from">From</label><input class="input" id="from" type="date" name="from" value="{{ $from->toDateString() }}"></div>
        <div><label class="label" for="to">To</label><input class="input" id="to" type="date" name="to" value="{{ $to->toDateString() }}"></div>
        <button class="btn btn-primary">Show</button>
    </form>

    <p class="mb-3 text-sm text-gray-500">Delivered orders placed between {{ $from->format('d M Y') }} and {{ $to->format('d M Y') }}. {{ $cancelledCount }} cancelled {{ Str::plural('order', $cancelledCount) }} in this period are excluded.</p>

    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase">Orders</p><p class="text-2xl font-extrabold">{{ $totals->orders }}</p></div>
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase">Product sales</p><p class="text-2xl font-extrabold">{{ money($totals->subtotal) }}</p></div>
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase">Delivery fees</p><p class="text-2xl font-extrabold">{{ money($totals->delivery_fees) }}</p></div>
        <div class="card p-4"><p class="text-xs text-gray-500 uppercase">Total collected</p><p class="text-2xl font-extrabold">{{ money($totals->total) }}</p></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card overflow-x-auto p-4">
            <h2 class="mb-2 font-bold">By payment method</h2>
            <table class="table">
                <thead><tr><th>Method</th><th class="text-right">Orders</th><th class="text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($byMethod as $row)
                        <tr><td>{{ \App\Enums\PaymentMethod::from($row->getRawOriginal('payment_method'))->label() }}</td><td class="text-right">{{ $row->orders }}</td><td class="text-right">{{ money($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-gray-500">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h2 class="mt-6 mb-2 font-bold">Daily</h2>
            <table class="table">
                <thead><tr><th>Day</th><th class="text-right">Orders</th><th class="text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($daily as $row)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('D, d M') }}</td><td class="text-right">{{ $row->orders }}</td><td class="text-right">{{ money($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-gray-500">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="card overflow-x-auto p-4">
            <h2 class="mb-2 font-bold">Top products</h2>
            <table class="table">
                <thead><tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Revenue</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($topProducts as $row)
                        <tr><td>{{ $row->product_name }}</td><td class="text-right">{{ qty($row->qty) }} {{ $row->unit }}</td><td class="text-right">{{ money($row->revenue) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-gray-500">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
