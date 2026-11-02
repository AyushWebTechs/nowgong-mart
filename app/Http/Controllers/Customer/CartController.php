<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ProductRecommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every mutation answers JSON for fetch() callers (no page reload) and redirects for plain form posts.
 */
class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(ProductRecommender $recommender): View
    {
        $summary = $this->cart->summary();
        $productIds = array_map(fn ($line) => $line['product']->id, $summary['lines']);

        return view('customer.cart', [
            'summary' => $summary,
            'suggestions' => $summary['is_empty'] ? collect() : $recommender->forCart($productIds, 8),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric'],
        ]);

        $product = Product::with('category')->findOrFail($data['product_id']);
        $this->cart->add($product, $data['quantity']);

        return $this->respond($request, "{$product->name} added to cart.");
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        abort_unless($this->cart->owns($cartItem), 404);

        $data = $request->validate(['quantity' => ['required', 'numeric']]);
        $this->cart->update($cartItem, $data['quantity']);

        return $this->respond($request, 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        abort_unless($this->cart->owns($cartItem), 404);

        $this->cart->remove($cartItem);

        return $this->respond($request, 'Item removed.');
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cart' => $this->cart->state()]);
        }

        return back()->with('success', $message);
    }
}
