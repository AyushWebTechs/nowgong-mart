<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InventoryService
{
    public function apply(Product $lockedProduct, string $change, MovementType $type, ?User $user, ?Order $order = null, ?string $note = null): InventoryMovement
    {
        $before = bcadd($lockedProduct->stock_qty ?? '0', '0', 3);
        $after = bcadd($before, $change, 3);

        if (bccomp($after, '0', 3) < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Not enough stock for {$lockedProduct->name}. Available: ".Money::qty($before)." {$lockedProduct->unit}.",
            ]);
        }

        $lockedProduct->forceFill(['stock_qty' => $after])->save();

        return InventoryMovement::create([
            'product_id' => $lockedProduct->id,
            'user_id' => $user?->id,
            'order_id' => $order?->id,
            'type' => $type,
            'quantity_change' => $change,
            'stock_before' => $before,
            'stock_after' => $after,
            'note' => $note,
        ]);
    }

    public function adjust(Product $product, MovementType $type, string $quantity, User $user, ?string $note = null): InventoryMovement
    {
        if (! in_array($type, MovementType::manual(), true)) {
            throw new InvalidArgumentException("{$type->value} is not a manual stock adjustment.");
        }

        return DB::transaction(function () use ($product, $type, $quantity, $user, $note) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $change = match ($type) {
                MovementType::Restock => bcadd($quantity, '0', 3),
                MovementType::CounterSale => bcmul($quantity, '-1', 3),
                MovementType::Correction => bcsub($quantity, $locked->stock_qty, 3),
            };

            return $this->apply($locked, $change, $type, $user, null, $note);
        });
    }

    public function openingStock(Product $product, string $quantity, ?User $user): void
    {
        if (bccomp($quantity, '0', 3) <= 0) {
            return;
        }

        DB::transaction(function () use ($product, $quantity, $user) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->apply($locked, bcadd($quantity, '0', 3), MovementType::Initial, $user, null, 'Opening stock');
        });
    }

    public function restoreForOrder(Order $order, ?User $user): void
    {
        $deducted = InventoryMovement::query()
            ->where('order_id', $order->id)
            ->whereIn('type', [MovementType::OnlineOrder, MovementType::OrderCancelled])
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(quantity_change) as net_change'))
            ->pluck('net_change', 'product_id');

        foreach ($deducted->sortKeys() as $productId => $netChange) {
            $restore = bcmul((string) $netChange, '-1', 3);

            if (bccomp($restore, '0', 3) <= 0) {
                continue;
            }

            $locked = Product::whereKey($productId)->lockForUpdate()->first();

            if ($locked) {
                $this->apply($locked, $restore, MovementType::OrderCancelled, $user, $order, "Order {$order->order_number} cancelled");
            }
        }
    }
}
