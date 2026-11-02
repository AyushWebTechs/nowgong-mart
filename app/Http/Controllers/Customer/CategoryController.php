<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        $categoryIds = $category->children()->active()->pluck('id')->push($category->id);

        $products = Product::active()
            ->with('category')
            ->whereIn('category_id', $categoryIds)
            ->orderBy('name')
            ->paginate(24);

        return view('customer.products.index', [
            'products' => $products,
            'search' => '',
            'category' => $category,
            'categories' => Category::active()->whereNull('parent_id')->ordered()->get(),
        ]);
    }
}
