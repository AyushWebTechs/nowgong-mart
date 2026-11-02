@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
    <h1 class="mb-4 text-xl font-bold">Customers</h1>

    <form method="GET" class="mb-3 flex gap-3">
        <input class="input max-w-xs" type="search" name="q" value="{{ request('q') }}" placeholder="Name or phone" aria-label="Search">
        <button class="btn btn-secondary">Search</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Joined</th><th class="text-right">Orders</th><th class="text-right">Delivered value</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="font-semibold">{{ $customer->name }}</td>
                        <td><a href="tel:+91{{ $customer->phone }}" class="text-brand-700">{{ $customer->phone }}</a></td>
                        <td class="text-xs">{{ $customer->email ?? '—' }}</td>
                        <td class="text-xs">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-right"><a href="{{ route('admin.orders.index', ['q' => $customer->phone]) }}" class="text-brand-700 hover:underline">{{ $customer->orders_count }}</a></td>
                        <td class="text-right">{{ money($customer->spent) }}</td>
                        <td><span class="badge {{ $customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $customer->is_active ? 'Active' : 'Blocked' }}</span></td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" data-confirm="{{ $customer->is_active ? 'Block' : 'Unblock' }} {{ $customer->name }}?">
                                @csrf
                                <button class="btn btn-secondary btn-sm">{{ $customer->is_active ? 'Block' : 'Unblock' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-gray-500">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
