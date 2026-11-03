@extends('layouts.app')

@section('title', 'Staff login')

@section('content')
    <div class="mx-auto max-w-sm animate-[fade-up_.4s_ease-out]">
        <div class="mb-5 text-center">
            <span class="mx-auto mb-3 grid size-14 place-items-center rounded-2xl bg-gray-900 text-white"><x-icon name="lock" class="size-7" /></span>
            <h1 class="text-2xl font-extrabold tracking-tight">Staff login</h1>
            <p class="mt-1 text-sm text-gray-500">For the shop owner and delivery partners.</p>
        </div>

        <form method="POST" action="{{ route('staff.login') }}" class="card space-y-4 p-5">
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
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Shopping with us? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Login with mobile code</a>
        </p>
    </div>
@endsection
