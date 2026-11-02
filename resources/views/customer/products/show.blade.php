@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <nav class="mb-4 flex items-center gap-1 overflow-x-auto text-sm whitespace-nowrap text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-brand-700">Home</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <a href="{{ route('categories.show', $product->category) }}" class="hover:text-brand-700">{{ $product->category->name }}</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <span class="truncate font-semibold text-gray-800">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-6 md:grid-cols-2 md:gap-10">
        <div class="relative aspect-square overflow-hidden rounded-3xl bg-white p-3 shadow-(--shadow-soft) md:sticky md:top-24 md:self-start">
            <div class="h-full w-full overflow-hidden rounded-2xl">
                @include('partials.product-image', ['product' => $product, 'loading' => 'eager', 'vt' => 'product-hero'])
            </div>
            @if ($product->discountPercent())
                <span class="absolute top-6 left-6 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 px-3 py-1.5 text-sm font-extrabold text-white shadow-lg">{{ $product->discountPercent() }}% OFF</span>
            @endif
        </div>

        <div class="reveal">
            <a href="{{ route('categories.show', $product->category) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $product->category->name }}</a>
            <h1 class="mt-3 text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Sold per {{ $product->unit }}</p>

            <div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-3xl font-extrabold tracking-tight">{{ money($product->price) }}</span>
                @if ($product->discountPercent())
                    <span class="text-gray-400 line-through">MRP {{ money($product->mrp) }}</span>
                    <span class="badge bg-emerald-100 text-emerald-800">You save {{ money(bcsub($product->mrp, $product->price, 2)) }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-500">Inclusive of all taxes</p>

            <p class="mt-3 text-sm">
                @if (! $product->isInStock())
                    <span class="inline-flex items-center gap-1.5 font-bold text-rose-600"><span class="size-2 rounded-full bg-rose-500"></span> Out of stock</span>
                @elseif ($product->isLowStock())
                    <span class="inline-flex items-center gap-1.5 font-bold text-amber-700"><span class="size-2 animate-pulse rounded-full bg-amber-500"></span> Hurry, only {{ qty($product->stock_qty) }} {{ $product->unit }} left</span>
                @else
                    <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span> In stock</span>
                @endif
            </p>

            <div class="mt-5 max-w-xs">
                @include('partials.add-to-cart', ['product' => $product, 'class' => 'atc-lg'])
            </div>

            <div class="mt-6 grid grid-cols-3 gap-2 text-center text-[11px] font-bold text-gray-600">
                <div class="rounded-2xl bg-white p-3 shadow-(--shadow-soft)"><x-icon name="truck" class="mx-auto mb-1 size-5 text-brand-600" />Fast delivery</div>
                <div class="rounded-2xl bg-white p-3 shadow-(--shadow-soft)"><x-icon name="wallet" class="mx-auto mb-1 size-5 text-brand-600" />COD & UPI</div>
                <div class="rounded-2xl bg-white p-3 shadow-(--shadow-soft)"><x-icon name="shield" class="mx-auto mb-1 size-5 text-brand-600" />Genuine product</div>
            </div>

            @if ($product->description)
                <div class="mt-6 rounded-2xl bg-white p-4 shadow-(--shadow-soft)">
                    <h2 class="mb-1 text-sm font-extrabold">About this product</h2>
                    <div class="text-sm leading-relaxed whitespace-pre-line text-gray-700">{{ $product->description }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-10">
        @include('partials.product-rail', [
            'title' => 'Frequently bought together',
            'subtitle' => 'Customers who bought this also added',
            'products' => $boughtWith,
        ])
        @include('partials.product-rail', [
            'title' => 'Similar products',
            'subtitle' => 'You might also like',
            'products' => $similar,
            'link' => route('categories.show', $product->category),
        ])
    </div>
@endsection
