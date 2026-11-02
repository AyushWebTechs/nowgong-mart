@extends('layouts.app')

@section('title', 'Cart')

@section('content')
    <h1 class="mb-4 text-xl font-bold">Your cart</h1>

    @if ($summary['is_empty'])
        <div class="card p-10 text-center">
            <p class="text-4xl" aria-hidden="true">🛒</p>
            <p class="mt-2 text-gray-600">Your cart is empty.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary mt-4">Browse products</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-3 lg:col-span-2">
                @foreach ($summary['lines'] as $line)
                    @php($product = $line['product'])
                    <div class="card flex gap-3 p-3 {{ $line['issue'] ? 'border-rose-300' : '' }}">
                        <a href="{{ route('products.show', $product) }}" class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                            @include('partials.product-image', ['product' => $product])
                        </a>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('products.show', $product) }}" class="font-semibold hover:text-brand-700">{{ $product->name }}</a>
                            <p class="text-xs text-gray-500">{{ money($product->price) }} per {{ $product->unit }}</p>
                            @if ($line['issue'])
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $line['issue'] }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('cart.items.update', $line['item']) }}" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <button type="button" data-step="-1" class="btn btn-secondary btn-sm" aria-label="Decrease">−</button>
                                    <input type="number" name="quantity" value="{{ qty($line['item']->quantity) }}" min="{{ $product->quantityStep() }}" step="{{ $product->quantityStep() }}" max="99" class="input w-16 py-1 text-center" aria-label="Quantity">
                                    <button type="button" data-step="1" class="btn btn-secondary btn-sm" aria-label="Increase">+</button>
                                    <button class="btn btn-secondary btn-sm">Update</button>
                                </form>
                                <form method="POST" action="{{ route('cart.items.destroy', $line['item']) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-rose-600 hover:underline">Remove</button>
                                </form>
                            </div>
                        </div>
                        <div class="text-right font-bold">{{ money($line['line_total']) }}</div>
                    </div>
                @endforeach
            </div>

            <aside class="card h-fit space-y-2 p-4 text-sm">
                <h2 class="text-base font-bold">Order summary</h2>
                <div class="flex justify-between"><span>Subtotal</span><span>{{ money($summary['subtotal']) }}</span></div>
                <div class="flex justify-between"><span>Delivery fee</span><span>{{ (float) $summary['delivery_fee'] ? money($summary['delivery_fee']) : 'FREE' }}</span></div>
                <div class="flex justify-between border-t pt-2 text-base font-bold"><span>Total</span><span>{{ money($summary['total']) }}</span></div>
                <p class="text-xs text-gray-500">Final prices are confirmed at checkout.</p>

                @if (! $summary['meets_minimum'])
                    <p class="rounded-lg bg-amber-50 p-2 text-xs text-amber-800">Minimum order is {{ money($summary['min_order']) }}. Add {{ money(bcsub($summary['min_order'], $summary['subtotal'], 2)) }} more.</p>
                @endif
                @if ($summary['has_issues'])
                    <p class="rounded-lg bg-rose-50 p-2 text-xs text-rose-700">Some items need your attention before checkout.</p>
                @endif

                @if ($summary['meets_minimum'] && ! $summary['has_issues'] && $store->bool('accepting_orders'))
                    <a href="{{ route('checkout.create') }}" class="btn btn-primary w-full">Proceed to checkout</a>
                @else
                    <button class="btn btn-primary w-full" disabled>Proceed to checkout</button>
                @endif
                <a href="{{ route('products.index') }}" class="block text-center text-brand-700 hover:underline">Continue shopping</a>
            </aside>
        </div>
    @endif
@endsection
