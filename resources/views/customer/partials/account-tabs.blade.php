<nav class="mb-4 flex gap-2 overflow-x-auto text-sm" aria-label="Account">
    @foreach ([['orders.index', 'My orders', 'orders.*'], ['addresses.index', 'Addresses', 'addresses.*'], ['profile.edit', 'Profile', 'profile.*']] as [$route, $label, $pattern])
        <a href="{{ route($route) }}" class="shrink-0 rounded-full border px-4 py-1.5 {{ request()->routeIs($pattern) ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white' }}">{{ $label }}</a>
    @endforeach
    <form method="POST" action="{{ route('logout') }}" class="md:hidden">
        @csrf
        <button class="shrink-0 rounded-full border border-gray-300 bg-white px-4 py-1.5">Logout</button>
    </form>
</nav>
