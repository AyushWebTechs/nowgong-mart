<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $guestCartKey = $request->session()->get(CartService::SESSION_KEY);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['phone' => 'These details do not match an active account.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->isCustomer()) {
            $cart->mergeGuestCart($user, $guestCartKey);
            $request->session()->forget(CartService::SESSION_KEY);

            return redirect()->intended(route('home'));
        }

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
