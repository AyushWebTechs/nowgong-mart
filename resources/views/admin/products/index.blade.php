@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ New product</a>
    </div>

    <form method="GET" class="card mb-4 flex flex-wrap gap-3 p-4">
        <input class="input max-w-xs" type="search" name="q" value="{{ request('q') }}" placeholder="Name or SKU" aria-label="Search">
        <select class="input max-w-xs" name="category_id" aria-label="Category">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select class="input max-w-[10rem]" name="status" aria-label="Status">
            <option value="">Any status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th></th><th>Product</th><th>Category</th><th class="text-right">Price</th><th class="text-right">Stock</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    <tr>
                        <td><div class="h-10 w-10 overflow-hidden rounded bg-gray-50">@include('partials.product-image', ['product' => $product, 'class' => 'h-10 w-10 object-cover text-base'])</div></td>
                        <td><span class="font-semibold">{{ $product->name }}</span><br><span class="text-xs text-gray-500">{{ $product->sku }} · per {{ $product->unit }}</span></td>
                        <td>{{ $product->category->name }}</td>
                        <td class="text-right">{{ money($product->price) }}@if ($product->mrp)<br><span class="text-xs text-gray-400">MRP {{ money($product->mrp) }}</span>@endif</td>
                        <td class="text-right {{ $product->isLowStock() ? 'font-semibold text-amber-600' : '' }}">{{ $product->track_stock ? qty($product->stock_qty) : '∞' }}</td>
                        <td>
                            <span class="badge {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                            @if ($product->is_featured) <span class="badge bg-amber-100 text-amber-800">Featured</span> @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" data-confirm="Delete {{ $product->name }}? Products in past orders are deactivated instead.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm text-rose-600 hover:bg-rose-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-500">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
