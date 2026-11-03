@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    @include('customer.partials.account-tabs')
    <h1 class="mb-4 text-xl font-bold">Profile</h1>

    <form method="POST" action="{{ route('profile.update') }}" class="card max-w-lg space-y-4 p-4">
        @csrf @method('PUT')
        <div>
            <label class="label">Mobile number</label>
            <p class="flex flex-wrap items-center gap-2 text-sm text-gray-700">
                +91 {{ $user->phone }}
                @if ($user->phone_verified_at)
                    <span class="badge bg-emerald-100 text-emerald-800"><x-icon name="check" class="size-3" stroke="3" /> Verified</span>
                @endif
                <span class="text-xs text-gray-500">(contact the shop to change it)</span>
            </p>
            <p class="mt-1 text-xs text-gray-500">You log in with a code sent to this number, so there is no password to remember.</p>
        </div>
        <div><label class="label" for="name">Name</label><input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div><label class="label" for="email">Email (optional)</label><input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}"></div>
        <button class="btn btn-primary">Save changes</button>
    </form>
@endsection
