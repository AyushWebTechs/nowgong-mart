<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(): View
    {
        return view('customer.cart', ['summary' => $this->cart->summary()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric'],
        ]);

        $product = Product::with('category')->findOrFail($data['product_id']);
        $this->cart->add($product, $data['quantity']);

        return back()->with('success', "{$product->name} added to cart.");
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($this->cart->owns($cartItem), 404);

        $data = $request->validate(['quantity' => ['required', 'numeric']]);
        $this->cart->update($cartItem, $data['quantity']);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        abort_unless($this->cart->owns($cartItem), 404);

        $this->cart->remove($cartItem);

        return back()->with('success', 'Item removed.');
    }
}
