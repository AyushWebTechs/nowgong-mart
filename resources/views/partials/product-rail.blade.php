{{-- Horizontally scrolling product row: title, optional "see all" link, products. --}}
@if ($products->isNotEmpty())
    <section class="mb-8" aria-label="{{ $title }}">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold tracking-tight text-gray-900">{{ $title }}</h2>
                @isset($subtitle)<p class="text-xs text-gray-500">{{ $subtitle }}</p>@endisset
            </div>
            @isset($link)
                <a href="{{ $link }}" class="inline-flex shrink-0 items-center text-sm font-bold text-brand-700 hover:text-brand-800">See all <x-icon name="chevron-right" class="size-4" /></a>
            @endisset
        </div>
        <div class="rail reveal">
            @foreach ($products as $product)
                @include('partials.product-card', ['product' => $product, 'i' => $loop->index])
            @endforeach
        </div>
    </section>
@endif
