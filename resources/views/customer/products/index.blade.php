@extends('layouts.app')

@section('title', $category?->name ?? ($search !== '' ? "Search: {$search}" : 'All products'))

@section('content')
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('products.index') }}" class="shrink-0 rounded-full border px-4 py-1.5 text-sm {{ ! $category ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white' }}">All</a>
        @foreach ($categories as $cat)
            <a href="{{ route('categories.show', $cat) }}" class="shrink-0 rounded-full border px-4 py-1.5 text-sm {{ $category?->id === $cat->id ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white' }}">{{ $cat->name }}</a>
        @endforeach
    </div>

    <h1 class="mb-1 text-xl font-bold">
        @if ($category)
            {{ $category->name }}
        @elseif ($search !== '')
            Results for “{{ $search }}”
        @else
            All products
        @endif
    </h1>
    @if ($category?->description)
        <p class="mb-3 text-sm text-gray-600">{{ $category->description }}</p>
    @endif
    <p class="mb-4 text-sm text-gray-500">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>

    @if ($products->isEmpty())
        <div class="card p-8 text-center text-gray-500">
            No products found. <a href="{{ route('products.index') }}" class="text-brand-700 underline">Browse everything</a>.
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @foreach ($products as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
@endsection
