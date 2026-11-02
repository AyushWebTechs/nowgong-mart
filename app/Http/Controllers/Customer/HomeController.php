<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public const ICE_CREAM_SLUG = 'ice-cream';

    public function index(): View
    {
        $categories = Category::active()->whereNull('parent_id')->ordered()->get();

        $iceCream = Product::active()
            ->whereHas('category', fn ($q) => $q->where('slug', self::ICE_CREAM_SLUG))
            ->orderBy('price')
            ->take(8)
            ->get();

        $popular = Product::active()
            ->with('category')
            ->orderByDesc('is_featured')
            ->withSum('orderItems as sold_qty', 'quantity')
            ->orderByDesc('sold_qty')
            ->latest('id')
            ->take(12)
            ->get();

        return view('customer.home', [
            'categories' => $categories,
            'iceCream' => $iceCream,
            'iceCreamFrom' => $iceCream->min('price'),
            'popular' => $popular,
        ]);
    }
}
