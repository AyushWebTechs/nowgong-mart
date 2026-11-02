{{--
    ADD button that turns into a − qty + stepper. Works without JS (plain POST);
    resources/js/cart.js upgrades it to instant, no-reload updates.
--}}
@php($inCart = $cartState['items'][$product->id] ?? null)
<div class="atc {{ $class ?? '' }}" data-atc data-product="{{ $product->id }}" data-step="{{ $product->quantityStep() }}"
     data-item="{{ $inCart['id'] ?? '' }}" data-qty="{{ $inCart['qty'] ?? 0 }}">
    @if (! $product->isInStock())
        <span class="atc-out">Out of stock</span>
    @else
        <form method="POST" action="{{ route('cart.items.store') }}" data-atc-add @if ($inCart) hidden @endif>
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            <button class="atc-btn" aria-label="Add {{ $product->name }} to cart">ADD</button>
        </form>
        <div class="atc-stepper" data-atc-stepper @unless ($inCart) hidden @endunless>
            <button type="button" data-atc-dec aria-label="Remove one {{ $product->unit }}"><x-icon name="minus" class="size-4" stroke="3" /></button>
            <span data-atc-qty aria-live="polite">{{ $inCart['qty'] ?? 0 }}</span>
            <button type="button" data-atc-inc aria-label="Add one more {{ $product->unit }}"><x-icon name="plus" class="size-4" stroke="3" /></button>
        </div>
    @endif
</div>
