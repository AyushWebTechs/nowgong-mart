<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, CartService $cart, StoreSettings $settings): View|RedirectResponse
    {
        $summary = $cart->summary();

        if ($summary['is_empty']) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        if ($summary['has_issues'] || ! $summary['meets_minimum']) {
            return redirect()->route('cart.index')->with('error', 'Please fix the items in your cart before checkout.');
        }

        return view('customer.checkout', [
            'summary' => $summary,
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest()->get(),
            'settings' => $settings,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(CheckoutRequest $request, CartService $cart, OrderService $orders): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $addressFields = ['recipient_name', 'phone', 'address_line', 'landmark', 'locality', 'pincode'];

        if (! empty($data['address_id'])) {
            $address = $user->addresses()->findOrFail($data['address_id'])->only($addressFields);
        } else {
            $address = Arr::only($data, $addressFields);
        }

        $currentCart = $cart->current();

        if (! $currentCart) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $order = $orders->place(
            $user,
            $currentCart,
            $address,
            PaymentMethod::from($data['payment_method']),
            $data['transaction_reference'] ?? null,
            $data['notes'] ?? null,
        );

        if (empty($data['address_id']) && $request->boolean('save_address')) {
            $user->addresses()->create([...$address, 'is_default' => ! $user->addresses()->exists()]);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order placed! We will confirm it shortly.');
    }
}
