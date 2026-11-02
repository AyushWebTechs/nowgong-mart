<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'guest_cart_key';

    public const MAX_QUANTITY = '99';

    public function __construct(private StoreSettings $settings) {}

    public function current(bool $create = false): ?Cart
    {
        if ($user = Auth::user()) {
            return $create
                ? Cart::firstOrCreate(['user_id' => $user->id])
                : Cart::where('user_id', $user->id)->first();
        }

        $key = session(self::SESSION_KEY);

        if (! $key) {
            if (! $create) {
                return null;
            }
            $key = Str::random(40);
            session([self::SESSION_KEY => $key]);
        }

        return $create
            ? Cart::firstOrCreate(['session_key' => $key])
            : Cart::where('session_key', $key)->first();
    }

    public function normalizeQuantity(Product $product, mixed $raw): string
    {
        if (! is_numeric($raw)) {
            throw ValidationException::withMessages(['quantity' => 'Enter a valid quantity.']);
        }

        $quantity = bcadd((string) $raw, '0', 3);

        if (bccomp($quantity, '0', 3) <= 0 || bccomp($quantity, self::MAX_QUANTITY, 3) > 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be between 1 and '.self::MAX_QUANTITY.'.']);
        }

        if (! $product->allowsFraction() && bccomp($quantity, bcadd($quantity, '0', 0), 3) !== 0) {
            throw ValidationException::withMessages(['quantity' => "{$product->name} is sold in whole {$product->unit}."]);
        }

        return $quantity;
    }

    public function add(Product $product, mixed $rawQuantity): void
    {
        $this->assertSellable($product);
        $quantity = $this->normalizeQuantity($product, $rawQuantity);

        $cart = $this->current(create: true);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $newQuantity = bcadd($item->quantity ?? '0', $quantity, 3);

        $this->assertStock($product, $newQuantity);

        $item->quantity = $newQuantity;
        $item->save();
        $cart->touch();
    }

    public function update(CartItem $item, mixed $rawQuantity): void
    {
        $product = $item->product;
        $quantity = $this->normalizeQuantity($product, $rawQuantity);
        $this->assertStock($product, $quantity);

        $item->update(['quantity' => $quantity]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function owns(CartItem $item): bool
    {
        $cart = $this->current();

        return $cart !== null && $item->cart_id === $cart->id;
    }

    public function mergeGuestCart(User $user, ?string $guestKey): void
    {
        if (! $guestKey) {
            return;
        }

        $guestCart = Cart::where('session_key', $guestKey)->with('items')->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $item = $userCart->items()->firstOrNew(['product_id' => $guestItem->product_id]);
            $item->quantity = bcadd($item->quantity ?? '0', $guestItem->quantity, 3);
            $item->save();
        }

        $guestCart->delete();
    }

    public function count(): int
    {
        return $this->current()?->items()->count() ?? 0;
    }

    /**
     * @return array{cart: ?Cart, lines: array, subtotal: string, delivery_fee: string, total: string, min_order: string, meets_minimum: bool, has_issues: bool, is_empty: bool}
     */
    public function summary(): array
    {
        $cart = $this->current();
        $items = $cart?->items()->with('product.category')->oldest('id')->get() ?? collect();

        $lines = [];
        $subtotal = '0.00';
        $hasIssues = false;

        foreach ($items as $item) {
            $product = $item->product;
            $issue = null;

            if (! $product->is_active || ! $product->category?->is_active) {
                $issue = 'No longer available. Please remove it.';
            } elseif (! $product->hasStockFor($item->quantity)) {
                $issue = $product->isInStock()
                    ? 'Only '.Money::qty($product->stock_qty)." {$product->unit} left."
                    : 'Out of stock. Please remove it.';
            }

            $lineTotal = Money::lineTotal($product->price, $item->quantity);
            $lines[] = ['item' => $item, 'product' => $product, 'line_total' => $lineTotal, 'issue' => $issue];

            if ($issue) {
                $hasIssues = true;
            } else {
                $subtotal = Money::add($subtotal, $lineTotal);
            }
        }

        $deliveryFee = $items->isEmpty() ? '0.00' : $this->settings->deliveryFeeFor($subtotal);
        $minOrder = $this->settings->money('min_order_value');

        return [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => Money::add($subtotal, $deliveryFee),
            'min_order' => $minOrder,
            'meets_minimum' => bccomp($subtotal, $minOrder, 2) >= 0,
            'has_issues' => $hasIssues,
            'is_empty' => $items->isEmpty(),
        ];
    }

    private function assertSellable(Product $product): void
    {
        if (! $product->is_active || ! $product->category?->is_active) {
            throw ValidationException::withMessages(['quantity' => "{$product->name} is not available."]);
        }
    }

    private function assertStock(Product $product, string $quantity): void
    {
        if (! $product->hasStockFor($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => $product->isInStock()
                    ? 'Only '.Money::qty($product->stock_qty)." {$product->unit} of {$product->name} available."
                    : "{$product->name} is out of stock.",
            ]);
        }
    }
}
