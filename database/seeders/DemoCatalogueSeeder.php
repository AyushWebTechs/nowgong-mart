<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCatalogueSeeder extends Seeder
{
    private const PRODUCTS = [
        'grocery' => [
            ['Aashirvaad Whole Wheat Atta 5 kg', 'pack', 265, 290, 20],
            ['Basmati Rice (loose)', 'kg', 95, null, 40],
            ['Toor Dal (loose)', 'kg', 140, null, 30],
            ['Fortune Soyabean Oil 1 L', 'pack', 150, 165, 25],
            ['Tata Salt 1 kg', 'pack', 28, 30, 50],
            ['Sugar (loose)', 'kg', 45, null, 60],
        ],
        'dairy' => [
            ['Amul Taaza Milk 500 ml', 'pack', 28, 28, 40],
            ['Fresh Curd 400 g', 'pack', 35, 40, 15],
            ['Paneer 200 g', 'pack', 90, 95, 10],
            ['Farm Eggs', 'dozen', 84, null, 12],
        ],
        'snacks' => [
            ['Haldiram Aloo Bhujia 200 g', 'pack', 55, 60, 30],
            ['Parle-G Biscuits 250 g', 'pack', 25, 25, 50],
            ['Lays Classic Salted 52 g', 'pack', 20, 20, 40],
            ['Dairy Milk Chocolate 50 g', 'pcs', 50, 50, 25],
        ],
        'beverages' => [
            ['Tata Tea Premium 250 g', 'pack', 125, 135, 20],
            ['Nescafe Classic 50 g', 'pack', 175, 185, 10],
            ['Coca-Cola 750 ml', 'pcs', 40, 40, 24],
            ['Frooti Mango 600 ml', 'pcs', 35, 35, 24],
        ],
        'ice-cream' => [
            ['Mini Ice Cream - Vanilla', 'pcs', 5, null, 100],
            ['Mini Ice Cream - Strawberry', 'pcs', 5, null, 100],
            ['Mini Ice Cream - Chocolate', 'pcs', 10, null, 80],
            ['Mini Ice Cream - Mango', 'pcs', 10, null, 80],
            ['Mini Ice Cream - Butterscotch', 'pcs', 10, null, 60],
            ['Kulfi Stick - Malai', 'pcs', 20, null, 40],
        ],
        'household' => [
            ['Surf Excel Easy Wash 1 kg', 'pack', 140, 150, 15],
            ['Vim Dishwash Bar 300 g', 'pcs', 30, 32, 30],
            ['Lifebuoy Soap 125 g', 'pcs', 38, 42, 30],
            ['Harpic Toilet Cleaner 500 ml', 'pcs', 99, 109, 12],
        ],
    ];

    public function run(InventoryService $inventory): void
    {
        foreach (self::PRODUCTS as $categorySlug => $products) {
            $category = Category::where('slug', $categorySlug)->firstOrFail();

            foreach ($products as $index => [$name, $unit, $price, $mrp, $stock]) {
                $slug = Str::slug($name);

                if (Product::where('slug', $slug)->exists()) {
                    continue;
                }

                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => $slug,
                    'sku' => strtoupper(substr($categorySlug, 0, 3)).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'unit' => $unit,
                    'price' => $price,
                    'mrp' => $mrp,
                    'cost_price' => round($price * 0.85, 2),
                    'low_stock_threshold' => $categorySlug === 'ice-cream' ? 20 : 5,
                    'track_stock' => true,
                    'is_active' => true,
                    'is_featured' => $index === 0,
                ]);

                $inventory->openingStock($product, (string) $stock, null);
            }
        }
    }
}
