<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => strtoupper(Str::random(8)),
            'unit' => 'pcs',
            'price' => '50.00',
            'mrp' => '55.00',
            'cost_price' => '40.00',
            'low_stock_threshold' => '2',
            'track_stock' => true,
            'is_active' => true,
        ];
    }

    public function stock(string|int $quantity): static
    {
        return $this->afterCreating(fn (Product $product) => $product->forceFill(['stock_qty' => $quantity])->save());
    }
}
