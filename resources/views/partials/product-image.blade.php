@if ($product->imageUrl())
    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" class="{{ $class ?? 'h-full w-full object-cover' }}">
@else
    <div class="flex {{ $class ?? 'h-full w-full' }} items-center justify-center bg-gradient-to-br from-brand-50 to-amber-50 text-3xl font-bold text-brand-700" aria-hidden="true">
        {{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}
    </div>
@endif
