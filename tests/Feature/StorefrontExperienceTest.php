<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    public function test_cart_endpoints_answer_json_for_ajax_callers(): void
    {
        $product = $this->product('10', '25.00');

        $add = $this->postJson(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart.count', 1)
            ->assertJsonPath('cart.subtotal', '50.00')
            ->assertJsonPath("cart.items.{$product->id}.qty", '2');

        $itemId = $add->json("cart.items.{$product->id}.id");

        $this->patchJson(route('cart.items.update', $itemId), ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('cart.subtotal', '75.00');

        $this->patchJson(route('cart.items.update', $itemId), ['quantity' => 500])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->deleteJson(route('cart.items.destroy', $itemId))
            ->assertOk()
            ->assertJsonPath('cart.count', 0);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_json_add_respects_stock(): void
    {
        $product = $this->product('1');

        $this->postJson(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_cannot_update_another_cart_via_json(): void
    {
        $product = $this->product();
        $this->addToCart(User::factory()->create(), $product);
        $item = CartItem::firstOrFail();

        $this->actingAs(User::factory()->create())
            ->patchJson(route('cart.items.update', $item), ['quantity' => 5])
            ->assertNotFound();
    }

    public function test_search_suggestions_understand_synonyms_plurals_and_typos(): void
    {
        $milk = $this->product('5', '28.00', ['name' => 'Amul Taaza Milk 500 ml', 'slug' => 'amul-taaza-milk']);
        $eggs = $this->product('5', '84.00', ['name' => 'Farm Egg Tray', 'slug' => 'farm-egg-tray']);

        $this->getJson(route('search.suggest', ['q' => 'doodh']))
            ->assertOk()
            ->assertJsonPath('products.0.id', $milk->id);

        $this->getJson(route('search.suggest', ['q' => 'eggs']))
            ->assertJsonPath('products.0.id', $eggs->id);

        $this->getJson(route('search.suggest', ['q' => 'milkk']))
            ->assertJsonPath('corrected', 'milk')
            ->assertJsonPath('products.0.id', $milk->id);

        $this->getJson(route('search.suggest', ['q' => 'zzzzqqq']))
            ->assertJsonCount(0, 'products');
    }

    public function test_search_suggestions_hide_inactive_products(): void
    {
        $this->product('5', '10.00', ['name' => 'Hidden Biscuit', 'slug' => 'hidden-biscuit', 'is_active' => false]);

        $this->getJson(route('search.suggest', ['q' => 'biscuit']))->assertJsonCount(0, 'products');
    }

    public function test_results_page_shows_typo_correction_and_sorts(): void
    {
        $this->product('5', '90.00', ['name' => 'Paneer Block', 'slug' => 'paneer-block']);
        $this->product('5', '40.00', ['name' => 'Paneer Cubes', 'slug' => 'paneer-cubes']);

        $this->get(route('products.index', ['q' => 'panner']))
            ->assertOk()
            ->assertSee('Showing results for')
            ->assertSee('Paneer Block');

        $this->get(route('products.index', ['q' => 'paneer', 'sort' => 'price_asc']))
            ->assertSeeInOrder(['Paneer Cubes', 'Paneer Block']);
    }

    public function test_product_page_recommends_similar_products(): void
    {
        $iceCream = Category::create(['name' => 'Ice Cream', 'slug' => 'ice-cream', 'is_active' => true]);
        $mango = $this->product('10', '10.00', ['name' => 'Mini Ice Cream - Mango', 'slug' => 'mango', 'category_id' => $iceCream->id]);
        $this->product('10', '10.00', ['name' => 'Mini Ice Cream - Vanilla', 'slug' => 'vanilla', 'category_id' => $iceCream->id]);

        $this->get(route('products.show', $mango))
            ->assertOk()
            ->assertSee('Similar products')
            ->assertSee('Mini Ice Cream - Vanilla');
    }

    public function test_cart_page_suggests_frequently_bought_together(): void
    {
        $customer = User::factory()->create();
        $bread = $this->product('20', '30.00', ['name' => 'Bread Loaf', 'slug' => 'bread']);
        $butter = $this->product('20', '55.00', ['name' => 'Butter Pack', 'slug' => 'butter']);

        $this->addToCart($customer, $butter);
        $this->placeOrder($customer, $bread);

        $this->addToCart($customer, $bread);
        $this->actingAs($customer)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('You might also like')
            ->assertSee('Butter Pack');
    }
}
