<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const CATEGORIES = [
        'grocery' => ['Grocery', 'Atta, rice, dal, oil, spices and daily staples.'],
        'dairy' => ['Dairy', 'Milk, curd, paneer, butter and eggs.'],
        'snacks' => ['Snacks', 'Namkeen, biscuits, chips and chocolates.'],
        'beverages' => ['Beverages', 'Tea, coffee, soft drinks and juices.'],
        'ice-cream' => ['Ice Cream', 'Mini ice cream pieces in many flavours.'],
        'household' => ['Household', 'Cleaning, laundry and personal care.'],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::CATEGORIES as $slug => [$name, $description]) {
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'sort_order' => ++$order, 'is_active' => true],
            );
        }
    }
}
