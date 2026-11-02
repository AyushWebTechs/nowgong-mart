@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <nav class="mb-4 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-brand-700">Home</a> ›
        <a href="{{ route('categories.show', $product->category) }}" class="hover:text-brand-700">{{ $product->category->name }}</a> ›
        <span class="text-gray-800">{{ $product->name }}</span>
    </nav>

    <div class="card grid gap-6 p-4 md:grid-cols-2 md:p-6">
        <div class="aspect-square overflow-hidden rounded-xl bg-gray-50">
            @include('partials.product-image', ['product' => $product])
        </div>
        <div>
            <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Sold per {{ $product->unit }} · SKU {{ $product->sku }}</p>

            <div class="mt-4 flex items-baseline gap-3">
                <span class="text-3xl font-extrabold">{{ money($product->price) }}</span>
                @if ($product->discountPercent())
                    <span class="text-gray-400 line-through">MRP {{ money($product->mrp) }}</span>
                    <span class="badge bg-amber-400 text-amber-950">{{ $product->discountPercent() }}% off</span>
                @endif
            </div>

            <p class="mt-2 text-sm">
                @if (! $product->isInStock())
                    <span class="font-semibold text-rose-600">Out of stock</span>
                @elseif ($product->isLowStock())
                    <span class="font-semibold text-amber-700">Only {{ qty($product->stock_qty) }} {{ $product->unit }} left</span>
                @else
                    <span class="font-semibold text-emerald-700">In stock</span>
                @endif
            </p>

            @if ($product->isInStock())
                <form method="POST" action="{{ route('cart.items.store') }}" class="mt-5 flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div>
                        <label for="quantity" class="label">Quantity ({{ $product->unit }})</label>
                        <div class="flex items-center gap-1">
                            <button type="button" data-step="-1" class="btn btn-secondary px-3" aria-label="Decrease">−</button>
                            <input id="quantity" type="number" name="quantity" value="1" min="{{ $product->quantityStep() }}" step="{{ $product->quantityStep() }}" max="99" class="input w-20 text-center">
                            <button type="button" data-step="1" class="btn btn-secondary px-3" aria-label="Increase">+</button>
                        </div>
                    </div>
                    <button class="btn btn-primary">Add to cart</button>
                </form>
            @endif

            @if ($product->description)
                <div class="mt-6 border-t pt-4 text-sm leading-relaxed whitespace-pre-line text-gray-700">{{ $product->description }}</div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <h2 class="mt-8 mb-3 text-lg font-bold">More from {{ $product->category->name }}</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($related as $item)
                @include('partials.product-card', ['product' => $item])
            @endforeach
        </div>
    @endif
@endsection
