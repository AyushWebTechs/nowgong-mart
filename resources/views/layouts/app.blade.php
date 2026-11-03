<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-[#f6f7f9] pb-24 text-gray-900 antialiased md:pb-0" data-cart-url="{{ url('/cart/items') }}">
    @php($onCartFlow = request()->routeIs('cart.*', 'checkout.*'))

    <header class="vt-header glass sticky top-0 z-40 border-b border-gray-200/70">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <img src="/icons/icon-192.png" alt="" class="size-10 rounded-xl shadow-sm ring-1 ring-black/5">
                <span class="leading-tight">
                    <span class="block text-[17px] font-extrabold tracking-tight">{{ $store->get('store_name') }}</span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-gray-500">
                        <x-icon name="zap" class="size-3 text-amber-500" stroke="2.5" /> Quick delivery · Nowgong, MP
                    </span>
                </span>
            </a>

            <button type="button" data-search-open class="group mx-4 hidden h-11 flex-1 items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 text-left text-sm text-gray-500 transition hover:border-brand-300 hover:bg-white md:flex">
                <x-icon name="search" class="size-5 text-gray-400 group-hover:text-brand-600" />
                <span class="flex-1">Search for <span class="font-semibold text-gray-700" data-typing>"milk"</span></span>
                <kbd class="rounded-md border border-gray-200 bg-white px-1.5 text-xs font-semibold text-gray-400">/</kbd>
            </button>

            <nav class="ml-auto flex items-center gap-1 text-sm font-semibold">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl px-3 py-2 hover:bg-gray-100">Admin</a>
                    @elseif (auth()->user()->isDelivery())
                        <a href="{{ route('delivery.index') }}" class="rounded-xl px-3 py-2 hover:bg-gray-100">Deliveries</a>
                    @else
                        <a href="{{ route('orders.index') }}" class="hidden items-center gap-1.5 rounded-xl px-3 py-2 hover:bg-gray-100 md:inline-flex"><x-icon name="package" class="size-4" /> Orders</a>
                        <a href="{{ route('profile.edit') }}" class="hidden items-center gap-1.5 rounded-xl px-3 py-2 hover:bg-gray-100 md:inline-flex"><x-icon name="user" class="size-4" /> Account</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button class="grid size-10 place-items-center rounded-xl text-gray-500 hover:bg-gray-100" aria-label="Logout" title="Logout"><x-icon name="logout" class="size-4" /></button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-3 py-2 hover:bg-gray-100">Login</a>
                @endauth
                <a href="{{ route('cart.index') }}" data-cart-target class="btn btn-primary ml-1 hidden h-11 rounded-2xl md:inline-flex" aria-label="Cart">
                    <x-icon name="bag" />
                    <span data-cart-pill>
                        @if ($cartCount)
                            {{ $cartCount }} {{ Str::plural('item', $cartCount) }} · {{ $cartState['subtotal_label'] }}
                        @else
                            My Cart
                        @endif
                    </span>
                </a>
            </nav>
        </div>
        <div class="px-4 pb-3 md:hidden">
            <button type="button" data-search-open class="flex h-11 w-full items-center gap-3 rounded-2xl border border-gray-200 bg-white px-4 text-left text-sm text-gray-500 shadow-sm">
                <x-icon name="search" class="size-5 text-gray-400" />
                <span>Search for <span class="font-semibold text-gray-700" data-typing>"milk"</span></span>
            </button>
        </div>
    </header>

    @unless ($store->bool('accepting_orders'))
        <div class="flex items-center justify-center gap-2 bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">
            <x-icon name="clock" class="size-4" /> We are not taking online orders right now. Hours: {{ $store->get('opening_hours') }}
        </div>
    @endunless

    <main class="mx-auto max-w-6xl px-4 py-5">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="mt-10 border-t border-gray-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm text-gray-600 sm:grid-cols-3">
            <div class="space-y-1">
                <p class="text-base font-extrabold text-gray-900">{{ $store->get('store_name') }}</p>
                <p class="flex items-start gap-1.5"><x-icon name="map-pin" class="mt-0.5 size-4" /> {{ $store->get('store_address') }}</p>
                <p class="flex items-center gap-1.5"><x-icon name="clock" class="size-4" /> {{ $store->get('opening_hours') }}</p>
                @if ($store->get('store_phone'))
                    <p>Call: <a class="font-semibold text-brand-700" href="tel:+91{{ $store->get('store_phone') }}">{{ $store->get('store_phone') }}</a></p>
                @endif
            </div>
            <div class="space-y-1.5">
                @foreach (\App\Http\Controllers\Customer\PageController::PAGES as $slug => $label)
                    <a class="block hover:text-brand-700" href="{{ route('pages.show', $slug) }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-start gap-2">
                @if ($store->get('whatsapp_number'))
                    <a class="btn btn-secondary" href="https://wa.me/91{{ $store->get('whatsapp_number') }}" target="_blank" rel="noopener"><x-icon name="message" class="size-4" /> WhatsApp us</a>
                @endif            </div>
        </div>
    </footer>

    {{-- Floating cart bar (mobile). --}}
    @unless ($onCartFlow)
        <a href="{{ route('cart.index') }}" data-cart-bar data-cart-target @unless ($cartCount) hidden @endunless
           class="vt-cartbar fixed inset-x-3 bottom-[76px] z-30 flex items-center gap-3 rounded-2xl bg-brand-600 p-2 pr-4 text-white shadow-xl shadow-brand-900/30 transition active:scale-[0.98] md:hidden">
            <span class="grid size-10 place-items-center rounded-xl bg-white/15"><x-icon name="bag" /></span>
            <span class="flex-1 leading-tight">
                <span class="block text-[11px] font-semibold text-brand-100" data-cart-count-label>{{ $cartCount }} {{ Str::plural('item', $cartCount) }}</span>
                <span class="block text-[15px] font-extrabold" data-cart-subtotal>{{ $cartState['subtotal_label'] }}</span>
            </span>
            <span class="flex items-center text-sm font-extrabold">View cart <x-icon name="chevron-right" class="size-4" stroke="3" /></span>
        </a>
    @endunless

    {{-- Bottom tab bar (mobile). --}}
    <nav class="vt-tabbar glass fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-gray-200/70 pb-[env(safe-area-inset-bottom)] text-center text-[11px] font-bold text-gray-500 md:hidden" aria-label="Mobile navigation">
        @php($tab = fn ($active) => $active ? 'text-brand-700' : '')
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-0.5 py-2 {{ $tab(request()->routeIs('home')) }}">
            <span class="grid h-7 w-12 place-items-center rounded-full transition {{ request()->routeIs('home') ? 'bg-brand-100' : '' }}"><x-icon name="home" /></span>Home
        </a>
        <a href="{{ route('products.index') }}" class="flex flex-col items-center gap-0.5 py-2 {{ $tab(request()->routeIs('products.*', 'categories.*')) }}">
            <span class="grid h-7 w-12 place-items-center rounded-full transition {{ request()->routeIs('products.*', 'categories.*') ? 'bg-brand-100' : '' }}"><x-icon name="grid" /></span>Shop
        </a>
        <a href="{{ route('cart.index') }}" class="flex flex-col items-center gap-0.5 py-2 {{ $tab($onCartFlow) }}" data-cart-target>
            <span class="relative grid h-7 w-12 place-items-center rounded-full transition {{ $onCartFlow ? 'bg-brand-100' : '' }}">
                <x-icon name="bag" />
                <span data-cart-count class="absolute -top-1 right-1 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] leading-none text-white" @unless ($cartCount) hidden @endunless>{{ $cartCount }}</span>
            </span>Cart
        </a>
        @auth
            @if (auth()->user()->isCustomer())
                <a href="{{ route('orders.index') }}" class="flex flex-col items-center gap-0.5 py-2 {{ $tab(request()->routeIs('orders.*', 'profile.*', 'addresses.*')) }}">
                    <span class="grid h-7 w-12 place-items-center rounded-full transition {{ request()->routeIs('orders.*', 'profile.*', 'addresses.*') ? 'bg-brand-100' : '' }}"><x-icon name="user" /></span>Account
                </a>
            @else
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full flex-col items-center gap-0.5 py-2"><span class="grid h-7 w-12 place-items-center"><x-icon name="logout" /></span>Logout</button></form>
            @endif
        @else
            <a href="{{ route('login') }}" class="flex flex-col items-center gap-0.5 py-2"><span class="grid h-7 w-12 place-items-center"><x-icon name="user" /></span>Login</a>
        @endauth
    </nav>

    @include('partials.search')
</body>
</html>
