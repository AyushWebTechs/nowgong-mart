<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white p-2 shadow-(--shadow-soft) transition duration-300 hover:-translate-y-0.5 hover:shadow-(--shadow-lift)" data-product-card style="--i: {{ $i ?? 0 }}">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden rounded-xl bg-gray-50" data-product-link>
        @include('partials.product-image', ['product' => $product, 'class' => 'h-full w-full object-cover transition duration-500 group-hover:scale-105'])
        @if ($product->discountPercent())
            <span class="absolute top-0 left-0 rounded-br-xl bg-gradient-to-br from-blue-600 to-indigo-600 px-2 py-1 text-[11px] leading-none font-extrabold text-white">{{ $product->discountPercent() }}%<br><span class="text-[9px] font-bold">OFF</span></span>
        @endif
        @if (! $product->isInStock())
            <span class="absolute inset-0 grid place-items-center bg-white/60 text-xs font-bold text-gray-600 backdrop-blur-[1px]">Out of stock</span>
        @elseif ($product->isLowStock())
            <span class="absolute bottom-1.5 left-1.5 rounded-md bg-amber-100/95 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">Only {{ qty($product->stock_qty) }} left</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col gap-0.5 px-1 pt-2">
        <span class="inline-flex w-fit items-center gap-1 rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600"><x-icon name="zap" class="size-3 text-amber-500" stroke="2.5" /> Fast</span>
        <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 min-h-10 text-[13px] leading-5 font-bold text-gray-900 hover:text-brand-700" data-product-link>{{ $product->name }}</a>
        <p class="text-xs text-gray-500">per {{ $product->unit }}</p>
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <div class="leading-tight">
                <span class="block text-sm font-extrabold text-gray-900">{{ money($product->price) }}</span>
                @if ($product->discountPercent())
                    <span class="block text-[11px] text-gray-400 line-through">{{ money($product->mrp) }}</span>
                @endif
            </div>
            @include('partials.add-to-cart', ['product' => $product, 'class' => 'w-[86px]'])
        </div>
    </div>
</article>
