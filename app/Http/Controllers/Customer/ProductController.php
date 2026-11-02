<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $products = Product::active()
            ->with('category')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('sku', $search)))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('customer.products.index', [
            'products' => $products,
            'search' => $search,
            'category' => null,
            'categories' => Category::active()->whereNull('parent_id')->ordered()->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->category?->is_active, 404);

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(6)
            ->get();

        return view('customer.products.show', ['product' => $product->load('category', 'images'), 'related' => $related]);
    }
}
