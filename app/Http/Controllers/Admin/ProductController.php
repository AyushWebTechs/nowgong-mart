<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $products = Product::with('category')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'track_stock' => true, 'low_stock_threshold' => 5, 'unit' => 'pcs']),
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function store(ProductRequest $request, InventoryService $inventory): RedirectResponse
    {
        $product = DB::transaction(function () use ($request, $inventory) {
            $product = Product::create($this->payload($request));
            $inventory->openingStock($product, (string) ($request->validated('opening_stock') ?? '0'), $request->user());

            return $product;
        });

        return redirect()->route('admin.products.index')->with('success', "Product {$product->name} created.");
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::ordered()->get()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($this->payload($request, $product));

        return redirect()->route('admin.products.index')->with('success', "Product {$product->name} updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('success', "{$product->name} appears in past orders, so it was deactivated instead of deleted.");
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function payload(ProductRequest $request, ?Product $product = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image', 'opening_stock']);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        foreach (['track_stock', 'is_active', 'is_featured'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        if ($product?->image_path && ($request->boolean('remove_image') || $request->hasFile('image'))) {
            Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        return $data;
    }
}
