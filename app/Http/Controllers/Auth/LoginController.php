<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Password login for shop staff (admin and delivery). Customers log in with an SMS code instead. */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.staff-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $staffOnly = fn ($query) => $query->where('role', '!=', UserRole::Customer->value);

        if (! Auth::attempt([...$credentials, 'is_active' => true, $staffOnly], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['phone' => 'These details do not match an active staff account.']);
        }

        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route($user->role->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
