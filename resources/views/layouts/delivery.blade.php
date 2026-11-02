<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    @stack('head')
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
    <header class="sticky top-0 z-30 bg-brand-700 text-white">
        <div class="mx-auto flex max-w-xl items-center gap-3 px-4 py-3">
            <a href="{{ route('delivery.index') }}" class="font-extrabold">🛵 Deliveries</a>
            <span class="ml-auto text-sm text-brand-100">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-md bg-brand-800 px-3 py-1 text-sm">Logout</button></form>
        </div>
    </header>
    <main class="mx-auto max-w-xl px-4 py-5">
        @include('partials.flash')
        @yield('content')
    </main>
</body>
</html>
