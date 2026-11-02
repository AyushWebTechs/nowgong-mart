@extends('layouts.admin')

@section('title', 'Delivery team')

@section('content')
    <h1 class="mb-4 text-xl font-bold">Delivery team</h1>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card overflow-x-auto lg:col-span-2">
            <table class="table">
                <thead><tr><th>Name</th><th>Phone</th><th>Open orders</th><th>Delivered</th><th>Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="font-semibold">{{ $user->name }}</td>
                            <td><a href="tel:+91{{ $user->phone }}" class="text-brand-700">{{ $user->phone }}</a></td>
                            <td>{{ $user->open_orders_count }}</td>
                            <td>{{ $user->delivered_count }}</td>
                            <td><span class="badge {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.delivery-users.toggle', $user) }}">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm">{{ $user->is_active ? 'Disable' : 'Enable' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-500">No delivery accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.delivery-users.store') }}" class="card h-fit space-y-3 p-4">
            @csrf
            <h2 class="font-bold">Add delivery person</h2>
            <div><label class="label" for="name">Name</label><input class="input" id="name" name="name" value="{{ old('name') }}" required></div>
            <div><label class="label" for="phone">Mobile (login ID)</label><input class="input" id="phone" name="phone" inputmode="numeric" maxlength="10" value="{{ old('phone') }}" required></div>
            <div><label class="label" for="password">Password (min. 8)</label><input class="input" id="password" type="text" name="password" autocomplete="new-password" required></div>
            <button class="btn btn-primary w-full">Create account</button>
        </form>
    </div>
@endsection
