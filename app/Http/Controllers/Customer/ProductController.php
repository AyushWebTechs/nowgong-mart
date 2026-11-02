<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductRecommender;
use App\Services\ProductSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, ProductSearch $search): View
    {
        ['term' => $term, 'corrected' => $corrected] = $search->resolve((string) $request->query('q'));
        $sort = (string) $request->query('sort');

        $products = $search->apply(Product::active()->with('category'), $term)
            ->sorted($sort)
            ->paginate(24)
            ->withQueryString();

        return view('customer.products.index', [
            'products' => $products,
            'search' => trim((string) $request->query('q')),
            'corrected' => $corrected,
            'sort' => $sort,
            'category' => null,
            'categories' => Category::active()->whereNull('parent_id')->ordered()->get(),
        ]);
    }

    public function show(Product $product, ProductRecommender $recommender): View
    {
        abort_unless($product->is_active && $product->category?->is_active, 404);

        $product->load('category', 'images');

        return view('customer.products.show', [
            'product' => $product,
            'similar' => $recommender->similar($product, 10),
            'boughtWith' => $recommender->boughtWith([$product->id], 6),
        ]);
    }
}
