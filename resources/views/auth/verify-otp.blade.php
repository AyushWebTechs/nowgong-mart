@extends('layouts.app')

@section('title', 'Verify number')

@section('content')
    <div class="mx-auto max-w-sm animate-[fade-up_.4s_ease-out]">
        <div class="mb-5 text-center">
            <span class="mx-auto mb-3 grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="shield" class="size-7" /></span>
            <h1 class="text-2xl font-extrabold tracking-tight">Enter verification code</h1>
            <p class="mt-1 text-sm text-gray-500">
                Sent to <strong class="text-gray-800">+91 {{ substr($phone, 0, 5) }} {{ substr($phone, 5) }}</strong> ·
                <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Change</a>
            </p>
        </div>

        @if ($demoCode)
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-center text-sm text-amber-900">
                <strong>Demo mode</strong>: SMS is not connected yet. Your code is
                <span class="ml-1 rounded-lg bg-white px-2 py-0.5 font-mono text-base font-bold tracking-widest">{{ $demoCode }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.verify') }}" class="card space-y-4 p-5" data-otp-form>
            @csrf
            <div>
                <label class="label" for="code">6-digit code</label>
                <input class="input py-3 text-center font-mono text-2xl font-bold tracking-[.6em]" id="code" name="code" type="text"
                       inputmode="numeric" autocomplete="one-time-code" maxlength="{{ config('otp.length') }}" pattern="[0-9]*" required autofocus data-otp-input>
            </div>

            @if ($isNew)
                <div>
                    <label class="label" for="name">Your name</label>
                    <input class="input" id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="100" required>
                    <p class="mt-1 text-xs text-gray-500">New here: we'll create your account with this number.</p>
                </div>
            @endif

            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" checked> Keep me logged in</label>
            <button class="btn btn-primary w-full py-3 text-base">{{ $isNew ? 'Verify & create account' : 'Verify & login' }}</button>
        </form>

        <form method="POST" action="{{ route('login.otp') }}" class="mt-4 text-center text-sm text-gray-500">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            Didn't get it?
            <button class="font-semibold text-brand-700 hover:underline disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline"
                    data-resend="{{ $resendIn }}" @disabled($resendIn > 0)>Resend code</button>
        </form>
    </div>
@endsection
