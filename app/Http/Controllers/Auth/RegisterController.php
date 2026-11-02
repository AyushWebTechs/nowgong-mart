<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, CartService $cart): RedirectResponse
    {
        $user = new User($request->validated());
        $user->forceFill(['role' => UserRole::Customer, 'is_active' => true])->save();

        $guestCartKey = $request->session()->get(CartService::SESSION_KEY);

        Auth::login($user);
        $request->session()->regenerate();

        $cart->mergeGuestCart($user, $guestCartKey);
        $request->session()->forget(CartService::SESSION_KEY);

        return redirect()->intended(route('home'))->with('success', 'Welcome to Nowgong Mart!');
    }
}
