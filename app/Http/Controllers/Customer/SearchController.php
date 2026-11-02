<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** Live suggestions for the search box. */
    public function suggest(Request $request, ProductSearch $search): JsonResponse
    {
        $term = mb_substr(trim((string) $request->query('q')), 0, 60);

        if (mb_strlen($term) < 1) {
            return response()->json(['products' => [], 'categories' => [], 'corrected' => null]);
        }

        $result = $search->suggest($term);

        return response()->json([
            'corrected' => $result['corrected'],
            'products' => $result['products']->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'unit' => $p->unit,
                'price' => money($p->price),
                'mrp' => $p->discountPercent() ? money($p->mrp) : null,
                'image' => $p->imageUrl(),
                'url' => route('products.show', $p),
                'category' => $p->category?->name,
                'in_stock' => $p->isInStock(),
                'step' => $p->quantityStep(),
            ]),
            'categories' => $result['categories']->map(fn (Category $c) => [
                'name' => $c->name,
                'url' => route('categories.show', $c),
            ]),
        ]);
    }
}
