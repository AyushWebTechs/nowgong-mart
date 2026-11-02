@extends('layouts.app')

@section('title', 'Cart')

@section('content')
    {{-- data-cart-live: cart.js morph-refreshes this page after each change so totals stay server-accurate. --}}
    <div data-cart-live>
        <h1 class="mb-4 text-xl font-extrabold tracking-tight sm:text-2xl">Your cart</h1>

        @if ($summary['is_empty'])
            <div class="card flex flex-col items-center px-6 py-12 text-center">
                <span class="grid size-20 place-items-center rounded-full bg-brand-50 text-brand-600"><x-icon name="bag" class="size-10" stroke="1.5" /></span>
                <p class="mt-4 text-lg font-extrabold">Your cart is empty</p>
                <p class="mt-1 text-sm text-gray-500">Add daily essentials and they'll show up here.</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary mt-5">Browse products</a>
            </div>
        @else
            @php($freeAbove = (float) $store->get('free_delivery_above'))
            @if ($freeAbove > 0)
                @php($progress = min(100, (float) $summary['subtotal'] / $freeAbove * 100))
                <div class="card mb-4 p-4">
                    <p class="flex items-center gap-2 text-sm font-bold {{ $progress >= 100 ? 'text-emerald-700' : 'text-gray-800' }}">
                        <x-icon name="truck" class="size-5" />
                        @if ($progress >= 100)
                            Yay! You've unlocked FREE delivery 🎉
                        @else
                            Add {{ money(max(0, $freeAbove - (float) $summary['subtotal'])) }} more for FREE delivery
                        @endif
                    </p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-emerald-400 transition-all duration-700" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            @endif

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="card divide-y divide-gray-100 lg:col-span-2">
                    @foreach ($summary['lines'] as $line)
                        @php($product = $line['product'])
                        <div class="flex gap-3 p-3 sm:p-4 {{ $line['issue'] ? 'bg-rose-50/60' : '' }}" id="line-{{ $line['item']->id }}">
                            <a href="{{ route('products.show', $product) }}" class="size-20 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                                @include('partials.product-image', ['product' => $product])
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <a href="{{ route('products.show', $product) }}" class="line-clamp-2 text-sm font-bold hover:text-brand-700">{{ $product->name }}</a>
                                <p class="text-xs text-gray-500">{{ money($product->price) }} / {{ $product->unit }}</p>
                                @if ($line['issue'])
                                    <p class="mt-1 text-xs font-bold text-rose-600">{{ $line['issue'] }}</p>
                                @endif
                                <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                                    <form method="POST" action="{{ route('cart.items.destroy', $line['item']) }}" data-cart-remove>
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1 text-xs font-bold text-gray-500 hover:text-rose-600"><x-icon name="trash" class="size-4" /> Remove</button>
                                    </form>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-extrabold tabular-nums">{{ money($line['line_total']) }}</span>
                                        @if (! $line['issue'] || $product->isInStock())
                                            @include('partials.add-to-cart', ['product' => $product, 'class' => 'w-[96px]'])
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <aside class="card h-fit space-y-2.5 p-4 text-sm lg:sticky lg:top-24">
                    <h2 class="text-base font-extrabold">Bill details</h2>
                    <div class="flex justify-between"><span class="text-gray-600">Item total</span><span class="font-semibold tabular-nums">{{ money($summary['subtotal']) }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Delivery fee</span>
                        <span class="font-semibold tabular-nums">{!! (float) $summary['delivery_fee'] ? e(money($summary['delivery_fee'])) : '<span class="text-emerald-700">FREE</span>' !!}</span>
                    </div>
                    <div class="flex justify-between border-t border-dashed border-gray-200 pt-2.5 text-base font-extrabold"><span>To pay</span><span class="tabular-nums">{{ money($summary['total']) }}</span></div>
                    <p class="text-xs text-gray-500">Final prices are confirmed at checkout.</p>

                    @if (! $summary['meets_minimum'])
                        <p class="rounded-xl bg-amber-50 p-2.5 text-xs font-semibold text-amber-800">Minimum order is {{ money($summary['min_order']) }}. Add {{ money(bcsub($summary['min_order'], $summary['subtotal'], 2)) }} more.</p>
                    @endif
                    @if ($summary['has_issues'])
                        <p class="rounded-xl bg-rose-50 p-2.5 text-xs font-semibold text-rose-700">Some items need your attention before checkout.</p>
                    @endif

                    @if ($summary['meets_minimum'] && ! $summary['has_issues'] && $store->bool('accepting_orders'))
                        <a href="{{ route('checkout.create') }}" class="btn btn-primary w-full py-3 text-base">Proceed to checkout <x-icon name="chevron-right" class="size-4" stroke="3" /></a>
                    @else
                        <button class="btn btn-primary w-full py-3 text-base" disabled>Proceed to checkout</button>
                    @endif
                    <a href="{{ route('products.index') }}" class="block pt-1 text-center font-semibold text-brand-700 hover:underline">Continue shopping</a>
                </aside>
            </div>

            <div class="mt-8">
                @include('partials.product-rail', [
                    'title' => 'You might also like',
                    'subtitle' => 'Based on what\'s in your cart',
                    'products' => $suggestions,
                ])
            </div>
        @endif
    </div>
@endsection
