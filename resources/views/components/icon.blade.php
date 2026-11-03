@props(['name', 'stroke' => 2])
{{-- Inline Lucide-style icons (MIT). Usage: <x-icon name="cart" class="size-5" /> --}}
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home')<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>@break
        @case('grid')<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>@break
        @case('bag')<path d="M6 7h12l1 14H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>@break
        @case('package')<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>@break
        @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
        @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>@break
        @case('x')<path d="M18 6 6 18M6 6l12 12"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('minus')<path d="M5 12h14"/>@break
        @case('arrow-left')<path d="M19 12H5M12 19l-7-7 7-7"/>@break
        @case('arrow-up-left')<path d="M17 17 7 7M7 15V7h8"/>@break
        @case('chevron-right')<path d="m9 18 6-6-6-6"/>@break
        @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
        @case('history')<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>@break
        @case('trending')<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>@break
        @case('trash')<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/>@break
        @case('truck')<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>@break
        @case('zap')<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>@break
        @case('sparkles')<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/>@break
        @case('logout')<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>@break
        @case('download')<path d="M12 3v12m0 0 5-5m-5 5-5-5"/><path d="M5 21h14"/>@break
        @case('shield')<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>@break
        @case('wallet')<rect x="3" y="6" width="18" height="14" rx="3"/><path d="M3 10h18M16 15h2"/>@break
        @case('map-pin')<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>@break
        @case('message')<path d="M21 12a8 8 0 0 1-11.8 7L3 21l2-6.2A8 8 0 1 1 21 12Z"/>@break
        @case('phone')<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/>@break
        @case('lock')<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>@break
        @case('tag')<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="8" cy="8" r="1.5"/>@break
        @case('sliders')<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>@break
        @case('check')<path d="m5 12 5 5 9-10"/>@break
        @case('alert')<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>@break
    @endswitch
</svg>
