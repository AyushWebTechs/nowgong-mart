<div class="card flex flex-col overflow-hidden">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square bg-gray-50">
        @include('partials.product-image', ['product' => $product])
        @if ($product->discountPercent())
            <span class="badge absolute top-2 left-2 bg-amber-400 text-amber-950">{{ $product->discountPercent() }}% off</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col gap-1 p-3">
        <a href="{{ route('products.show', $product) }}" class="line-clamp-2 text-sm font-semibold text-gray-900 hover:text-brand-700">{{ $product->name }}</a>
        <p class="text-xs text-gray-500">per {{ $product->unit }}</p>
        <div class="flex items-baseline gap-2">
            <span class="font-bold text-gray-900">{{ money($product->price) }}</span>
            @if ($product->discountPercent())
                <span class="text-xs text-gray-400 line-through">{{ money($product->mrp) }}</span>
            @endif
        </div>
        <div class="mt-auto pt-2">
            @if ($product->isInStock())
                <form method="POST" action="{{ route('cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn btn-primary btn-sm w-full">Add to cart</button>
                </form>
            @else
                <span class="block rounded-lg bg-gray-100 py-1.5 text-center text-xs font-semibold text-gray-500">Out of stock</span>
            @endif
        </div>
    </div>
</div>
