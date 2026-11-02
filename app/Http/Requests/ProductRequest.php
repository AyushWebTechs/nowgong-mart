<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'alpha_dash', 'max:170', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'alpha_dash', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'price' => ['required', 'decimal:0,2', 'min:0.01', 'max:99999999'],
            'mrp' => ['nullable', 'decimal:0,2', 'gte:price', 'max:99999999'],
            'cost_price' => ['nullable', 'decimal:0,2', 'min:0', 'max:99999999'],
            'opening_stock' => [$product ? 'prohibited' : 'nullable', 'decimal:0,3', 'min:0', 'max:9999999'],
            'low_stock_threshold' => ['required', 'decimal:0,3', 'min:0', 'max:9999999'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['mrp.gte' => 'MRP cannot be lower than the selling price.'];
    }
}
