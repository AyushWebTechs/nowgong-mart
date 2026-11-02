<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $products = Product::with('category')
            ->where('track_stock', true)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->boolean('low'), fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventory.index', [
            'products' => $products,
            'allProducts' => Product::where('track_stock', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'stock_qty']),
            'types' => MovementType::manual(),
        ]);
    }

    public function store(StockAdjustmentRequest $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        $movement = $inventory->adjust($product, MovementType::from($data['type']), (string) $data['quantity'], $request->user(), $data['note'] ?? null);

        return back()->with('success', "{$product->name}: stock ".Money::qty($movement->stock_before).' → '.Money::qty($movement->stock_after)." {$product->unit}.");
    }

    public function movements(Request $request): View
    {
        $movements = InventoryMovement::with('product', 'user', 'order')
            ->when($request->query('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.inventory.movements', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'types' => MovementType::cases(),
        ]);
    }
}
