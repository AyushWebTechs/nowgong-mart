<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $categoryIds = $category->children()->active()->pluck('id')->push($category->id);
        $sort = (string) $request->query('sort');

        $products = Product::active()
            ->with('category')
            ->whereIn('category_id', $categoryIds)
            ->sorted($sort)
            ->paginate(24)
            ->withQueryString();

        return view('customer.products.index', [
            'products' => $products,
            'search' => '',
            'corrected' => null,
            'sort' => $sort,
            'category' => $category,
            'categories' => Category::active()->whereNull('parent_id')->ordered()->get(),
        ]);
    }
}
