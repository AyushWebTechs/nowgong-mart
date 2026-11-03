@extends('layouts.admin')

@section('title', 'Login history')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">Login history</h1>
            <p class="text-sm text-gray-500">Every verification code sent to a customer's mobile. Today: {{ $today['sent'] }} sent, {{ $today['verified'] }} verified.</p>
        </div>
        @if (config('otp.demo'))
            <p class="rounded-lg bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-900">Demo mode: codes are shown on screen, not sent by SMS.</p>
        @endif
    </div>

    <form method="GET" class="mb-3 flex gap-3">
        <input class="input max-w-xs" type="search" name="q" value="{{ request('q') }}" placeholder="Mobile number" aria-label="Search by mobile number">
        <button class="btn btn-secondary">Search</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Time</th><th>Mobile</th><th>Customer</th><th>Status</th><th class="text-right">Tries</th><th>Sent via</th><th>IP / device</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($codes as $code)
                    <tr>
                        <td class="whitespace-nowrap text-xs">{{ $code->created_at->format('d M Y, h:i:s A') }}</td>
                        <td class="font-mono">{{ $code->phone }}</td>
                        <td>{{ $code->user?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $code->statusBadgeClass() }}">{{ $code->statusLabel() }}</span>
                            @if ($code->verified_at)<span class="block text-[11px] text-gray-500">{{ $code->verified_at->format('h:i:s A') }}</span>@endif
                        </td>
                        <td class="text-right">{{ $code->attempts }}</td>
                        <td class="text-xs">{{ $code->driver }}</td>
                        <td class="max-w-xs text-xs text-gray-500"><span class="font-mono">{{ $code->ip_address }}</span><br><span class="line-clamp-1" title="{{ $code->user_agent }}">{{ $code->user_agent }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-500">No codes sent yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $codes->links() }}</div>
@endsection
