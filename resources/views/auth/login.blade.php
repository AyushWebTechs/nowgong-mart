@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="mx-auto max-w-sm animate-[fade-up_.4s_ease-out]">
        <div class="mb-5 text-center">
            <span class="mx-auto mb-3 grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="phone" class="size-7" /></span>
            <h1 class="text-2xl font-extrabold tracking-tight">Login or sign up</h1>
            <p class="mt-1 text-sm text-gray-500">We'll text a 6-digit code to verify your number.</p>
        </div>

        <form method="POST" action="{{ route('login.otp') }}" class="card space-y-4 p-5">
            @csrf
            <div>
                <label class="label" for="phone">Mobile number</label>
                <div class="flex items-stretch overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20">
                    <span class="grid place-items-center border-r border-gray-200 bg-gray-50 px-3 text-sm font-semibold text-gray-600">+91</span>
                    <input class="w-full border-0 px-3 py-3 text-lg font-semibold tracking-wider outline-none" id="phone" name="phone" type="tel" inputmode="numeric"
                           maxlength="10" pattern="[6-9][0-9]{9}" autocomplete="tel-national" value="{{ old('phone') }}" placeholder="98765 43210" required autofocus>
                </div>
            </div>
            <button class="btn btn-primary w-full py-3 text-base">Send code</button>
            <p class="text-center text-xs text-gray-500">By continuing you agree to our <a class="underline" href="{{ route('pages.show', 'terms') }}">terms</a> and <a class="underline" href="{{ route('pages.show', 'privacy') }}">privacy policy</a>.</p>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Shop staff or delivery partner? <a href="{{ route('staff.login') }}" class="font-semibold text-brand-700 hover:underline">Staff login</a>
        </p>
    </div>
@endsection
