<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * "Similar products" (category + name overlap + price band) and
 * "frequently bought together" (co-occurrence in past orders).
 */
class ProductRecommender
{
    /** Words that say nothing about what a product is. */
    private const NOISE = ['pack', 'loose', 'classic', 'fresh', 'premium', 'mini', 'stick', 'easy', 'the', 'and', 'with', 'kg', 'ml', 'litre', 'pcs'];

    /** @return Collection<int, Product> */
    public function similar(Product $product, int $limit = 8): Collection
    {
        $keywords = $this->keywords($product->name);
        $parentId = $product->category?->parent_id;

        return Product::active()
            ->with('category')
            ->whereKeyNot($product->id)
            ->get()
            ->map(function (Product $candidate) use ($product, $keywords, $parentId) {
                $score = 0.0;

                if ($candidate->category_id === $product->category_id) {
                    $score += 5;
                } elseif ($parentId && $candidate->category?->parent_id === $parentId) {
                    $score += 2;
                }

                $score += 3 * count(array_intersect($keywords, $this->keywords($candidate->name)));

                $high = max((float) $product->price, (float) $candidate->price, 1);
                $score += 2 * (1 - min(1, abs((float) $product->price - (float) $candidate->price) / $high));

                $score += $candidate->isInStock() ? 1 : -4;

                return [$candidate, $score];
            })
            ->filter(fn ($pair) => $pair[1] >= 3)
            ->sortByDesc(fn ($pair) => $pair[1])
            ->take($limit)
            ->map(fn ($pair) => $pair[0])
            ->values();
    }

    /**
     * Products that most often share an order with any of $productIds.
     *
     * @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    public function boughtWith(array $productIds, int $limit = 6): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $ranked = OrderItem::query()
            ->from('order_items as other')
            ->join('order_items as mine', 'mine.order_id', '=', 'other.order_id')
            ->whereIn('mine.product_id', $productIds)
            ->whereNotIn('other.product_id', $productIds)
            ->whereNotNull('other.product_id')
            ->groupBy('other.product_id')
            ->selectRaw('other.product_id, COUNT(*) as together')
            ->orderByDesc('together')
            ->take($limit * 2)
            ->pluck('together', 'other.product_id');

        if ($ranked->isEmpty()) {
            return collect();
        }

        return Product::active()
            ->with('category')
            ->whereIn('id', $ranked->keys())
            ->get()
            ->filter->isInStock()
            ->sortByDesc(fn (Product $p) => $ranked[$p->id] ?? 0)
            ->take($limit)
            ->values();
    }

    /**
     * Suggestions for a cart: bought-together first, topped up with items similar to what's in it.
     *
     * @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    public function forCart(array $productIds, int $limit = 8): Collection
    {
        $picks = $this->boughtWith($productIds, $limit);

        foreach (Product::with('category')->whereIn('id', $productIds)->get() as $product) {
            if ($picks->count() >= $limit) {
                break;
            }
            $picks = $picks->merge($this->similar($product, 4))->unique('id');
        }

        return $picks->reject(fn (Product $p) => in_array($p->id, $productIds, true))
            ->filter->isInStock()
            ->take($limit)
            ->values();
    }

    /** @return list<string> */
    private function keywords(string $name): array
    {
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_diff(
            array_filter($words, fn ($w) => mb_strlen($w) >= 3),
            self::NOISE
        ));
    }
}
