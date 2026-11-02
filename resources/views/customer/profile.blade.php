@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    @include('customer.partials.account-tabs')
    <h1 class="mb-4 text-xl font-bold">Profile</h1>

    <form method="POST" action="{{ route('profile.update') }}" class="card max-w-lg space-y-4 p-4">
        @csrf @method('PUT')
        <div>
            <label class="label">Mobile number</label>
            <p class="text-sm text-gray-700">{{ $user->phone }} <span class="text-xs text-gray-500">(contact the shop to change it)</span></p>
        </div>
        <div><label class="label" for="name">Name</label><input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div><label class="label" for="email">Email (optional)</label><input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}"></div>
        <fieldset class="space-y-3 border-t pt-4">
            <legend class="text-sm font-bold">Change password</legend>
            <div><label class="label" for="current_password">Current password</label><input class="input" id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
            <div><label class="label" for="password">New password</label><input class="input" id="password" type="password" name="password" autocomplete="new-password"></div>
            <div><label class="label" for="password_confirmation">Confirm new password</label><input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
        </fieldset>
        <button class="btn btn-primary">Save changes</button>
    </form>
@endsection
