@extends('layouts.app')

@section('content')
    {{-- Hero --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-500 to-emerald-400 px-5 py-7 text-white shadow-lg shadow-brand-900/10 sm:px-8 sm:py-10">
        <div class="pointer-events-none absolute -top-16 -right-10 size-56 animate-pulse rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-1/3 size-64 rounded-full bg-amber-300/20 blur-3xl"></div>
        <div class="relative max-w-xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-bold backdrop-blur">
                <x-icon name="zap" class="size-3.5 text-amber-300" stroke="2.5" /> {{ $store->get('service_area_note') }}
            </span>
            <h1 class="mt-3 text-[28px] leading-tight font-extrabold tracking-tight sm:text-4xl">Groceries & ice cream,<br>at your door in minutes.</h1>
            <p class="mt-2 text-sm font-medium text-brand-50/90">
                Cash on Delivery or UPI ·
                @if ((float) $store->get('free_delivery_above') > 0)
                    Free delivery above {{ money($store->get('free_delivery_above')) }}
                @else
                    Delivery {{ money($store->get('delivery_fee')) }}
                @endif
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('products.index') }}" class="btn bg-white text-brand-700 shadow-lg hover:bg-brand-50">Start shopping <x-icon name="chevron-right" class="size-4" stroke="3" /></a>
                <button type="button" data-search-open class="btn bg-white/15 text-white backdrop-blur hover:bg-white/25"><x-icon name="search" class="size-4" /> Search</button>
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <div class="mb-7 grid grid-cols-3 gap-2 text-center text-[11px] font-bold text-gray-600 sm:text-xs">
        <div class="flex flex-col items-center gap-1 rounded-2xl bg-white p-3 shadow-(--shadow-soft) sm:flex-row sm:justify-center"><x-icon name="truck" class="size-5 text-brand-600" /> Fast local delivery</div>
        <div class="flex flex-col items-center gap-1 rounded-2xl bg-white p-3 shadow-(--shadow-soft) sm:flex-row sm:justify-center"><x-icon name="wallet" class="size-5 text-brand-600" /> COD & UPI</div>
        <div class="flex flex-col items-center gap-1 rounded-2xl bg-white p-3 shadow-(--shadow-soft) sm:flex-row sm:justify-center"><x-icon name="shield" class="size-5 text-brand-600" /> Fresh & genuine</div>
    </div>

    {{-- Categories --}}
    <section class="mb-8" aria-labelledby="categories-heading">
        <h2 id="categories-heading" class="mb-3 text-lg font-extrabold tracking-tight">Shop by category</h2>
        @php($tiles = [
            'grocery' => ['🌾', 'from-amber-100 to-orange-50'],
            'dairy' => ['🥛', 'from-sky-100 to-blue-50'],
            'snacks' => ['🍿', 'from-rose-100 to-orange-50'],
            'beverages' => ['🥤', 'from-emerald-100 to-lime-50'],
            'ice-cream' => ['🍦', 'from-pink-100 to-fuchsia-50'],
            'household' => ['🧼', 'from-violet-100 to-indigo-50'],
        ])
        <div class="reveal grid grid-cols-3 gap-3 sm:grid-cols-6">
            @foreach ($categories as $category)
                @php([$emoji, $gradient] = $tiles[$category->slug] ?? [null, 'from-gray-100 to-gray-50'])
                <a href="{{ route('categories.show', $category) }}" style="--i: {{ $loop->index }}"
                   class="group flex flex-col items-center gap-2 rounded-2xl bg-gradient-to-b {{ $gradient }} px-2 pt-4 pb-3 text-center transition duration-300 hover:-translate-y-1 hover:shadow-(--shadow-lift) active:scale-95">
                    @if ($emoji)
                        <span class="text-4xl transition duration-300 group-hover:scale-110" aria-hidden="true">{{ $emoji }}</span>
                    @elseif ($category->image_path)
                        <img src="{{ Storage::disk('public')->url($category->image_path) }}" alt="" class="size-12 rounded-full object-cover">
                    @else
                        <span class="text-4xl" aria-hidden="true">🛍️</span>
                    @endif
                    <span class="text-xs font-bold text-gray-800 sm:text-sm">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    @include('partials.product-rail', [
        'title' => 'Top deals 🔥',
        'subtitle' => 'Biggest savings on MRP right now',
        'products' => $deals,
        'link' => route('products.index', ['sort' => 'discount']),
    ])

    @if ($iceCream->isNotEmpty())
        <section class="mb-8 rounded-3xl bg-gradient-to-br from-pink-100 via-rose-50 to-sky-100 p-4 sm:p-5" aria-labelledby="icecream-heading">
            <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 id="icecream-heading" class="text-lg font-extrabold tracking-tight text-pink-950">🍦 Mini Ice Cream</h2>
                    <p class="text-xs font-medium text-pink-900/80">From {{ money($iceCreamFrom) }} a piece. {{ $store->get('ice_cream_note') }}</p>
                </div>
                <a href="{{ route('categories.show', \App\Http\Controllers\Customer\HomeController::ICE_CREAM_SLUG) }}" class="inline-flex items-center text-sm font-bold text-pink-900">All flavours <x-icon name="chevron-right" class="size-4" /></a>
            </div>
            <div class="rail reveal">
                @foreach ($iceCream as $product)
                    @include('partials.product-card', ['product' => $product, 'i' => $loop->index])
                @endforeach
            </div>
        </section>
    @endif

    <section aria-labelledby="popular-heading">
        <h2 id="popular-heading" class="mb-3 text-lg font-extrabold tracking-tight">Popular right now</h2>
        @if ($popular->isEmpty())
            <p class="text-sm text-gray-500">Products will appear here soon.</p>
        @else
            <div class="reveal grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($popular as $product)
                    @include('partials.product-card', ['product' => $product, 'i' => $loop->index])
                @endforeach
            </div>
        @endif
    </section>
@endsection
