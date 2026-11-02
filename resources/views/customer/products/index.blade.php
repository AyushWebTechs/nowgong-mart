@extends('layouts.app')

@section('title', $category?->name ?? ($search !== '' ? "Search: {$search}" : 'All products'))

@section('content')
    <div class="sticky top-[118px] z-20 -mx-4 mb-4 bg-[#f6f7f9]/90 px-4 py-2 backdrop-blur md:top-[66px]">
        <div class="rail !mx-0 !px-0 !pb-0 [&>*]:!w-auto">
            <a href="{{ route('products.index') }}" class="chip {{ ! $category && $search === '' ? 'chip-active' : '' }}">All</a>
            @foreach ($categories as $cat)
                <a href="{{ route('categories.show', $cat) }}" class="chip {{ $category?->id === $cat->id ? 'chip-active' : '' }}">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight sm:text-2xl">
                @if ($category)
                    {{ $category->name }}
                @elseif ($search !== '')
                    Results for “{{ $corrected ?? $search }}”
                @else
                    All products
                @endif
            </h1>
            @if ($corrected)
                <p class="mt-0.5 text-sm text-gray-600">No exact match for “{{ $search }}”. Showing results for <span class="font-bold text-brand-700">{{ $corrected }}</span>.</p>
            @endif
            @if ($category?->description)
                <p class="mt-0.5 text-sm text-gray-600">{{ $category->description }}</p>
            @endif
            <p class="mt-0.5 text-xs font-semibold text-gray-500">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>
        </div>

        <form method="GET" class="flex items-center gap-2" data-autosubmit>
            @if ($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
            <label class="relative">
                <span class="sr-only">Sort products</span>
                <x-icon name="sliders" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-500" />
                <select name="sort" class="input appearance-none rounded-full py-2 pr-8 pl-9 text-sm font-semibold">
                    <option value="">{{ $search !== '' ? 'Most relevant' : 'Name A–Z' }}</option>
                    @foreach (\App\Models\Product::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button class="btn btn-secondary btn-sm">Apply</button></noscript>
        </form>
    </div>

    @if ($products->isEmpty())
        <div class="card flex flex-col items-center p-10 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-gray-100 text-gray-400"><x-icon name="search" class="size-8" /></span>
            <p class="mt-3 font-bold text-gray-800">No products found</p>
            <p class="mt-1 text-sm text-gray-500">Try another word like “milk”, “atta” or “ice cream”.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary mt-4">Browse everything</a>
        </div>
    @else
        <div class="reveal grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6" data-infinite>
            @foreach ($products as $product)
                @include('partials.product-card', ['product' => $product, 'i' => $loop->index])
            @endforeach
        </div>
        {{-- Infinite scroll loads the next page; the links remain as a no-JS fallback. --}}
        @if ($products->hasMorePages())
            <div class="mt-6 flex justify-center" data-infinite-more>
                <a href="{{ $products->nextPageUrl() }}" class="btn btn-secondary" data-infinite-next data-turbo="false">Load more</a>
            </div>
        @endif
    @endif
@endsection
