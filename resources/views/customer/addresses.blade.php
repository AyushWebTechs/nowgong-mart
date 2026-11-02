@extends('layouts.app')

@section('title', 'Addresses')

@section('content')
    @include('customer.partials.account-tabs')
    <h1 class="mb-4 text-xl font-bold">Saved addresses</h1>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-3">
            @forelse ($addresses as $address)
                <div class="card p-4 text-sm">
                    <p class="font-semibold">
                        {{ $address->recipient_name }} · {{ $address->phone }}
                        @if ($address->is_default)
                            <span class="badge ml-1 bg-brand-100 text-brand-800">Default</span>
                        @endif
                    </p>
                    <p class="text-gray-600">{{ $address->oneLine() }}</p>
                    <div class="mt-2 flex gap-3">
                        @unless ($address->is_default)
                            <form method="POST" action="{{ route('addresses.default', $address) }}">@csrf<button class="text-xs font-semibold text-brand-700 hover:underline">Make default</button></form>
                        @endunless
                        <form method="POST" action="{{ route('addresses.destroy', $address) }}" data-confirm="Remove this address?">
                            @csrf @method('DELETE')
                            <button class="text-xs font-semibold text-rose-600 hover:underline">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="card p-6 text-center text-sm text-gray-500">No saved addresses yet.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('addresses.store') }}" class="card grid h-fit gap-3 p-4 sm:grid-cols-2">
            @csrf
            <h2 class="font-bold sm:col-span-2">Add a new address</h2>
            <div><label class="label" for="recipient_name">Name</label><input class="input" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}" required></div>
            <div><label class="label" for="phone">Mobile</label><input class="input" id="phone" name="phone" inputmode="numeric" maxlength="10" value="{{ old('phone', auth()->user()->phone) }}" required></div>
            <div class="sm:col-span-2"><label class="label" for="address_line">House no., street</label><input class="input" id="address_line" name="address_line" value="{{ old('address_line') }}" required></div>
            <div><label class="label" for="landmark">Landmark</label><input class="input" id="landmark" name="landmark" value="{{ old('landmark') }}"></div>
            <div><label class="label" for="locality">Locality</label><input class="input" id="locality" name="locality" value="{{ old('locality') }}" required></div>
            <div><label class="label" for="pincode">Pincode</label><input class="input" id="pincode" name="pincode" inputmode="numeric" maxlength="6" value="{{ old('pincode', '471201') }}" required></div>
            <div class="flex items-end"><button class="btn btn-primary w-full">Save address</button></div>
        </form>
    </div>
@endsection
