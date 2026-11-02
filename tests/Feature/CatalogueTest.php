<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_product_with_image_and_opening_stock(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Mini Ice Cream - Vanilla',
            'sku' => 'ICE-001',
            'unit' => 'pcs',
            'price' => '5',
            'opening_stock' => '50',
            'low_stock_threshold' => '10',
            'track_stock' => '1',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('vanilla.jpg', 400, 400),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::where('sku', 'ICE-001')->firstOrFail();
        $this->assertSame('50.000', $product->stock_qty);
        $this->assertSame('5.00', $product->price);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => MovementType::Initial->value, 'stock_after' => '50.000']);
    }

    public function test_product_upload_rejects_non_images(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Bad upload',
            'sku' => 'BAD-1',
            'unit' => 'pcs',
            'price' => '10',
            'low_stock_threshold' => '1',
            'image' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('image');
    }

    public function test_editing_a_product_cannot_change_stock_directly(): void
    {
        $product = $this->product('10');

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => 'pcs',
            'price' => '60',
            'low_stock_threshold' => '2',
            'opening_stock' => '999',
            'stock_qty' => '999',
        ])->assertSessionHasErrors('opening_stock');

        $this->assertSame('10.000', $product->fresh()->stock_qty);
    }

    public function test_deactivated_product_is_hidden_from_storefront(): void
    {
        $product = $this->product();
        $product->update(['is_active' => false]);

        $this->get(route('products.show', $product))->assertNotFound();
        $this->get(route('products.index'))->assertDontSee($product->name);
    }

    public function test_deleting_a_product_with_order_history_deactivates_it(): void
    {
        $customer = User::factory()->create();
        $product = $this->product('10');
        $order = $this->placeOrder($customer, $product, 1);

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertSame($product->name, $order->items()->first()->product_name);
    }

    public function test_stock_adjustments_are_logged(): void
    {
        $product = $this->product('10');

        $this->actingAs($this->admin)->post(route('admin.inventory.store'), ['product_id' => $product->id, 'type' => 'counter_sale', 'quantity' => '3']);
        $this->assertSame('7.000', $product->fresh()->stock_qty);

        $this->actingAs($this->admin)->post(route('admin.inventory.store'), ['product_id' => $product->id, 'type' => 'restock', 'quantity' => '5']);
        $this->assertSame('12.000', $product->fresh()->stock_qty);

        $this->actingAs($this->admin)->post(route('admin.inventory.store'), ['product_id' => $product->id, 'type' => 'correction', 'quantity' => '9']);
        $this->assertSame('9.000', $product->fresh()->stock_qty);

        $this->actingAs($this->admin)->post(route('admin.inventory.store'), ['product_id' => $product->id, 'type' => 'counter_sale', 'quantity' => '20'])
            ->assertSessionHasErrors('quantity');
        $this->assertSame('9.000', $product->fresh()->stock_qty);

        $movement = InventoryMovement::where('product_id', $product->id)->latest('id')->first();
        $this->assertSame(MovementType::Correction, $movement->type);
        $this->assertSame('12.000', $movement->stock_before);
        $this->assertSame($this->admin->id, $movement->user_id);
    }
}
