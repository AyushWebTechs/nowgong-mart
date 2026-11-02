@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'New product')

@section('content')
    <a href="{{ route('admin.products.index') }}" class="text-sm text-brand-700 hover:underline">← Products</a>
    <h1 class="mt-2 mb-4 text-xl font-bold">{{ $product->exists ? "Edit {$product->name}" : 'New product' }}</h1>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
          class="card grid max-w-3xl gap-4 p-5 sm:grid-cols-2">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="sm:col-span-2"><label class="label" for="name">Name</label><input class="input" id="name" name="name" value="{{ old('name', $product->name) }}" required></div>
        <div>
            <label class="label" for="category_id">Category</label>
            <select class="input" id="category_id" name="category_id" required>
                <option value="">Choose…</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label" for="sku">SKU (unique code)</label><input class="input" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required></div>
        <div>
            <label class="label" for="unit">Unit</label>
            <select class="input" id="unit" name="unit" required>
                @foreach (\App\Models\Product::UNITS as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">kg and litre allow quarter quantities (0.25). Track each ice cream flavour as its own product in pcs.</p>
        </div>
        <div><label class="label" for="slug">URL slug (auto if blank)</label><input class="input" id="slug" name="slug" value="{{ old('slug', $product->slug) }}"></div>
        <div><label class="label" for="price">Selling price (₹)</label><input class="input" id="price" name="price" inputmode="decimal" value="{{ old('price', $product->price) }}" required></div>
        <div><label class="label" for="mrp">MRP (₹, optional)</label><input class="input" id="mrp" name="mrp" inputmode="decimal" value="{{ old('mrp', $product->mrp) }}"></div>
        <div><label class="label" for="cost_price">Cost price (₹, private)</label><input class="input" id="cost_price" name="cost_price" inputmode="decimal" value="{{ old('cost_price', $product->cost_price) }}"></div>
        @if ($product->exists)
            <div>
                <span class="label">Current stock</span>
                <p class="py-2 text-sm">{{ $product->track_stock ? qty($product->stock_qty).' '.$product->unit : 'Not tracked' }} · <a href="{{ route('admin.inventory.index', ['q' => $product->sku]) }}" class="text-brand-700 underline">adjust in Inventory</a></p>
            </div>
        @else
            <div><label class="label" for="opening_stock">Opening stock</label><input class="input" id="opening_stock" name="opening_stock" inputmode="decimal" value="{{ old('opening_stock', 0) }}"></div>
        @endif
        <div><label class="label" for="low_stock_threshold">Low-stock alert at</label><input class="input" id="low_stock_threshold" name="low_stock_threshold" inputmode="decimal" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" required></div>
        <div class="sm:col-span-2"><label class="label" for="description">Description</label><textarea class="input" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea></div>
        <div>
            <label class="label" for="image">Image (JPG/PNG/WebP, max 2 MB)</label>
            <input class="input" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            @if ($product->image_path)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $product->imageUrl() }}" alt="" class="h-16 w-16 rounded object-cover">
                    <label class="flex items-center gap-1 text-xs"><input type="hidden" name="remove_image" value="0"><input type="checkbox" name="remove_image" value="1"> Remove image</label>
                </div>
            @endif
        </div>
        <div class="space-y-2 self-end text-sm">
            @foreach (['is_active' => 'Visible in the shop', 'track_stock' => 'Track stock for this product', 'is_featured' => 'Feature on the home page'] as $flag => $label)
                <label class="flex items-center gap-2">
                    <input type="hidden" name="{{ $flag }}" value="0">
                    <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $product->{$flag}))> {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="sm:col-span-2"><button class="btn btn-primary">Save product</button></div>
    </form>
@endsection
