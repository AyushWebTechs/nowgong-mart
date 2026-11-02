@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-4 text-center text-xl font-bold">Create your account</h1>
        <form method="POST" action="{{ route('register') }}" class="card space-y-4 p-5">
            @csrf
            <div><label class="label" for="name">Full name</label><input class="input" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus></div>
            <div><label class="label" for="phone">Mobile number</label><input class="input" id="phone" name="phone" inputmode="numeric" maxlength="10" value="{{ old('phone') }}" autocomplete="tel-national" required></div>
            <div><label class="label" for="email">Email (optional)</label><input class="input" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email"></div>
            <div><label class="label" for="password">Password (min. 8 characters)</label><input class="input" id="password" type="password" name="password" autocomplete="new-password" required></div>
            <div><label class="label" for="password_confirmation">Confirm password</label><input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
            <button class="btn btn-primary w-full">Create account</button>
            <p class="text-center text-xs text-gray-500">By signing up you agree to our <a class="underline" href="{{ route('pages.show', 'terms') }}">terms</a> and <a class="underline" href="{{ route('pages.show', 'privacy') }}">privacy policy</a>.</p>
            <p class="text-center text-sm text-gray-600">Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Login</a></p>
        </form>
    </div>
@endsection
