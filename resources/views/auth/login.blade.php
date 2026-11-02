@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-4 text-center text-xl font-bold">Login</h1>
        <form method="POST" action="{{ route('login') }}" class="card space-y-4 p-5">
            @csrf
            <div>
                <label class="label" for="phone">Mobile number</label>
                <input class="input" id="phone" name="phone" inputmode="numeric" autocomplete="username" value="{{ old('phone') }}" required autofocus>
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input class="input" id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Keep me logged in</label>
            <button class="btn btn-primary w-full">Login</button>
            <p class="text-center text-sm text-gray-600">New here? <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Create an account</a></p>
        </form>
    </div>
@endsection
