@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-bold">Inventory</h1>
        <a href="{{ route('admin.inventory.movements') }}" class="btn btn-secondary">Stock ledger</a>
    </div>

    <form method="POST" action="{{ route('admin.inventory.store') }}" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        @csrf
        <h2 class="font-bold sm:col-span-2 lg:col-span-5">Counter sale / stock adjustment</h2>
        <div class="lg:col-span-2">
            <label class="label" for="product_id">Product</label>
            <select class="input" id="product_id" name="product_id" required>
                <option value="">Choose…</option>
                @foreach ($allProducts as $product)
                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->sku }}) — {{ qty($product->stock_qty) }} {{ $product->unit }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="type">Type</label>
            <select class="input" id="type" name="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', 'counter_sale') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="quantity">Quantity</label>
            <input class="input" id="quantity" name="quantity" inputmode="decimal" value="{{ old('quantity') }}" required>
        </div>
        <div>
            <label class="label" for="note">Note</label>
            <input class="input" id="note" name="note" value="{{ old('note') }}" placeholder="optional">
        </div>
        <p class="text-xs text-gray-500 sm:col-span-2 lg:col-span-4">
            Counter sale subtracts, restock adds, and stock count correction sets the exact quantity you counted on the shelf. Every change is logged in the stock ledger.
        </p>
        <button class="btn btn-primary">Save</button>
    </form>

    <form method="GET" class="mb-3 flex flex-wrap gap-3">
        <input class="input max-w-xs" type="search" name="q" value="{{ request('q') }}" placeholder="Name or SKU" aria-label="Search">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="low" value="1" @checked(request()->boolean('low'))> Low stock only</label>
        <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Product</th><th>Category</th><th class="text-right">In stock</th><th class="text-right">Alert at</th><th></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    <tr class="{{ $product->isLowStock() ? 'bg-amber-50' : '' }}">
                        <td><span class="font-semibold">{{ $product->name }}</span><br><span class="text-xs text-gray-500">{{ $product->sku }}</span></td>
                        <td>{{ $product->category->name }}</td>
                        <td class="text-right font-semibold {{ (float) $product->stock_qty <= 0 ? 'text-rose-600' : '' }}">{{ qty($product->stock_qty) }} {{ $product->unit }}</td>
                        <td class="text-right text-gray-500">{{ qty($product->low_stock_threshold) }}</td>
                        <td class="text-right"><a href="{{ route('admin.inventory.movements', ['product_id' => $product->id]) }}" class="text-xs text-brand-700 hover:underline">History</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-500">No tracked products.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
