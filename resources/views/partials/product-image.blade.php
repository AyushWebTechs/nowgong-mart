@if ($product->imageUrl())
    {{-- Lazy images shimmer then fade in; eager (hero) images render immediately so view transitions capture them. --}}
    @php($lazy = ($loading ?? 'lazy') === 'lazy')
    <span class="{{ $lazy ? 'skeleton' : '' }} block h-full w-full">
        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="{{ $lazy ? 'lazy' : 'eager' }}" decoding="async"
             class="{{ $lazy ? 'fade-img' : '' }} {{ $class ?? 'h-full w-full object-cover' }}" @isset($vt) style="view-transition-name: {{ $vt }}" @endisset>
    </span>
@else
    <div class="flex {{ $class ?? 'h-full w-full' }} items-center justify-center bg-gradient-to-br from-brand-50 to-amber-50 text-3xl font-bold text-brand-700" aria-hidden="true"
         @isset($vt) style="view-transition-name: {{ $vt }}" @endisset>
        {{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}
    </div>
@endif
