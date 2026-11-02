<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    @stack('head')
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
    @php
        $links = [
            ['admin.dashboard', 'Dashboard', 'admin.dashboard'],
            ['admin.orders.index', 'Orders', 'admin.orders.*'],
            ['admin.products.index', 'Products', 'admin.products.*'],
            ['admin.categories.index', 'Categories', 'admin.categories.*'],
            ['admin.inventory.index', 'Inventory', 'admin.inventory.*'],
            ['admin.delivery-users.index', 'Delivery team', 'admin.delivery-users.*'],
            ['admin.customers.index', 'Customers', 'admin.customers.*'],
            ['admin.reports.sales', 'Sales report', 'admin.reports.*'],
            ['admin.settings.edit', 'Settings', 'admin.settings.*'],
        ];
    @endphp
    <header class="bg-gray-900 text-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
            <a href="{{ route('admin.dashboard') }}" class="font-extrabold">{{ $store->get('store_name') }} <span class="font-normal text-gray-400">Admin</span></a>
            <a href="{{ route('home') }}" class="ml-auto text-sm text-gray-300 hover:text-white">View shop ↗</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-gray-300 hover:text-white">Logout</button></form>
        </div>
        <nav class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 pb-2 text-sm" aria-label="Admin navigation">
            @foreach ($links as [$route, $label, $pattern])
                <a href="{{ route($route) }}" class="shrink-0 rounded-md px-3 py-1.5 {{ request()->routeIs($pattern) ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6">
        @include('partials.flash')
        @yield('content')
    </main>
</body>
</html>
