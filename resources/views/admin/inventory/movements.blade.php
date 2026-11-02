@extends('layouts.admin')

@section('title', 'Stock ledger')

@section('content')
    <a href="{{ route('admin.inventory.index') }}" class="text-sm text-brand-700 hover:underline">← Inventory</a>
    <h1 class="mt-2 mb-4 text-xl font-bold">Stock ledger</h1>

    <form method="GET" class="mb-3 flex flex-wrap gap-3">
        <select class="input max-w-xs" name="product_id" aria-label="Product">
            <option value="">All products</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select class="input max-w-xs" name="type" aria-label="Type">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>When</th><th>Product</th><th>Type</th><th class="text-right">Change</th><th class="text-right">Before → after</th><th>By</th><th>Note</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movements as $movement)
                    <tr>
                        <td class="whitespace-nowrap text-xs">{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $movement->product->name }}</td>
                        <td>{{ $movement->type->label() }}</td>
                        <td class="text-right font-semibold {{ (float) $movement->quantity_change < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ (float) $movement->quantity_change > 0 ? '+' : '' }}{{ qty($movement->quantity_change) }}</td>
                        <td class="text-right whitespace-nowrap">{{ qty($movement->stock_before) }} → {{ qty($movement->stock_after) }}</td>
                        <td>{{ $movement->user?->name ?? '—' }}</td>
                        <td class="text-xs">
                            @if ($movement->order)
                                <a href="{{ route('admin.orders.show', $movement->order) }}" class="text-brand-700 hover:underline">{{ $movement->order->order_number }}</a>
                            @endif
                            {{ $movement->order ? '' : $movement->note }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-500">No stock movements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
@endsection
