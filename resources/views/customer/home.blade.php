@extends('layouts.app')

@section('content')
    <section class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-brand-600 to-brand-500 px-6 py-8 text-white">
        <p class="text-sm font-medium text-brand-100">{{ $store->get('service_area_note') }}</p>
        <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">Daily groceries & mini ice cream,<br>delivered to your door.</h1>
        <p class="mt-2 text-sm text-brand-100">
            Cash on Delivery or UPI ·
            @if ((float) $store->get('free_delivery_above') > 0)
                Free delivery above {{ money($store->get('free_delivery_above')) }}
            @else
                Delivery {{ money($store->get('delivery_fee')) }}
            @endif
        </p>
        <a href="{{ route('products.index') }}" class="btn mt-4 bg-white text-brand-700 hover:bg-brand-50">Start shopping</a>
    </section>

    <section class="mb-8" aria-labelledby="categories-heading">
        <h2 id="categories-heading" class="mb-3 text-lg font-bold">Shop by category</h2>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
            @php($icons = ['grocery' => '🌾', 'dairy' => '🥛', 'snacks' => '🍪', 'beverages' => '🥤', 'ice-cream' => '🍦', 'household' => '🧼'])
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}" class="card flex flex-col items-center gap-1 px-2 py-4 text-center hover:border-brand-500">
                    @if ($category->image_path)
                        <img src="{{ Storage::disk('public')->url($category->image_path) }}" alt="" class="h-12 w-12 rounded-full object-cover">
                    @else
                        <span class="text-3xl" aria-hidden="true">{{ $icons[$category->slug] ?? '🛍️' }}</span>
                    @endif
                    <span class="text-sm font-semibold">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    @if ($iceCream->isNotEmpty())
        <section class="mb-8 rounded-2xl bg-gradient-to-r from-pink-100 via-amber-50 to-sky-100 p-5" aria-labelledby="icecream-heading">
            <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 id="icecream-heading" class="text-lg font-extrabold text-pink-900">🍦 Mini Ice Cream</h2>
                    <p class="text-sm text-pink-900/80">Starting at {{ money($iceCreamFrom) }} a piece. {{ $store->get('ice_cream_note') }}</p>
                </div>
                <a href="{{ route('categories.show', \App\Http\Controllers\Customer\HomeController::ICE_CREAM_SLUG) }}" class="text-sm font-semibold text-pink-900 underline">See all flavours</a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($iceCream->take(4) as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    <section aria-labelledby="popular-heading">
        <h2 id="popular-heading" class="mb-3 text-lg font-bold">Popular right now</h2>
        @if ($popular->isEmpty())
            <p class="text-sm text-gray-500">Products will appear here soon.</p>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($popular as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>
@endsection
