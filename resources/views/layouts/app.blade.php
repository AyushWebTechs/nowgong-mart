<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-gray-50 pb-20 text-gray-900 antialiased md:pb-0">
    <header class="sticky top-0 z-30 bg-brand-600 text-white shadow">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
                <img src="/icons/icon-192.png" alt="" class="h-9 w-9 rounded-lg bg-white">
                <span class="leading-tight">
                    <span class="block text-lg font-extrabold">{{ $store->get('store_name') }}</span>
                    <span class="block text-[11px] text-brand-100">Delivering in Nowgong, MP</span>
                </span>
            </a>
            <form action="{{ route('products.index') }}" method="GET" class="ml-2 hidden flex-1 md:block" role="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search atta, milk, ice cream…" class="input border-0" aria-label="Search products">
            </form>
            <nav class="ml-auto flex items-center gap-1 text-sm font-medium">
                <a href="{{ route('cart.index') }}" class="relative rounded-lg px-3 py-2 hover:bg-brand-700" aria-label="Cart">
                    🛒 <span class="hidden sm:inline">Cart</span>
                    @if ($cartCount)
                        <span class="absolute -top-0.5 -right-0.5 rounded-full bg-amber-400 px-1.5 text-xs font-bold text-amber-950">{{ $cartCount }}</span>
                    @endif
                </a>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 hover:bg-brand-700">Admin</a>
                    @elseif (auth()->user()->isDelivery())
                        <a href="{{ route('delivery.index') }}" class="rounded-lg px-3 py-2 hover:bg-brand-700">Deliveries</a>
                    @else
                        <a href="{{ route('orders.index') }}" class="hidden rounded-lg px-3 py-2 hover:bg-brand-700 md:inline-block">My orders</a>
                        <a href="{{ route('profile.edit') }}" class="hidden rounded-lg px-3 py-2 hover:bg-brand-700 md:inline-block">Account</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button class="rounded-lg px-3 py-2 hover:bg-brand-700">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 hover:bg-brand-700">Login</a>
                @endauth
            </nav>
        </div>
        <form action="{{ route('products.index') }}" method="GET" class="px-4 pb-3 md:hidden" role="search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products…" class="input border-0" aria-label="Search products">
        </form>
    </header>

    @unless ($store->bool('accepting_orders'))
        <div class="bg-amber-100 px-4 py-2 text-center text-sm font-medium text-amber-900">
            We are not taking online orders right now. Hours: {{ $store->get('opening_hours') }}
        </div>
    @endunless

    <main class="mx-auto max-w-6xl px-4 py-6">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="mt-10 border-t border-gray-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm text-gray-600 sm:grid-cols-3">
            <div>
                <p class="font-bold text-gray-900">{{ $store->get('store_name') }}</p>
                <p>{{ $store->get('store_address') }}</p>
                <p>Open: {{ $store->get('opening_hours') }}</p>
                @if ($store->get('store_phone'))
                    <p>Call: <a class="text-brand-700 underline" href="tel:+91{{ $store->get('store_phone') }}">{{ $store->get('store_phone') }}</a></p>
                @endif
            </div>
            <div class="space-y-1">
                @foreach (\App\Http\Controllers\Customer\PageController::PAGES as $slug => $label)
                    <a class="block hover:text-brand-700" href="{{ route('pages.show', $slug) }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="space-y-2">
                @if ($store->get('whatsapp_number'))
                    <a class="btn btn-secondary" href="https://wa.me/91{{ $store->get('whatsapp_number') }}" target="_blank" rel="noopener">💬 WhatsApp us</a>
                @endif
                <button type="button" data-install class="btn btn-primary hidden">📲 Install app</button>
            </div>
        </div>
    </footer>

    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-gray-200 bg-white text-center text-xs font-medium text-gray-600 md:hidden" aria-label="Mobile navigation">
        <a href="{{ route('home') }}" class="py-2 {{ request()->routeIs('home') ? 'text-brand-700' : '' }}"><span class="block text-lg">🏠</span>Home</a>
        <a href="{{ route('products.index') }}" class="py-2 {{ request()->routeIs('products.*', 'categories.*') ? 'text-brand-700' : '' }}"><span class="block text-lg">🧺</span>Shop</a>
        <a href="{{ route('cart.index') }}" class="py-2 {{ request()->routeIs('cart.*', 'checkout.*') ? 'text-brand-700' : '' }}"><span class="block text-lg">🛒</span>Cart{{ $cartCount ? " ({$cartCount})" : '' }}</a>
        @auth
            @if (auth()->user()->isCustomer())
                <a href="{{ route('orders.index') }}" class="py-2 {{ request()->routeIs('orders.*', 'profile.*', 'addresses.*') ? 'text-brand-700' : '' }}"><span class="block text-lg">📦</span>Orders</a>
            @else
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full py-2"><span class="block text-lg">🚪</span>Logout</button></form>
            @endif
        @else
            <a href="{{ route('login') }}" class="py-2"><span class="block text-lg">👤</span>Login</a>
        @endauth
    </nav>
</body>
</html>
