<?php

namespace Tests;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Services\StoreSettings;
use Database\Seeders\StoreSettingSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeSmsSender;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function fakeSms(): FakeSmsSender
    {
        return $this->app->instance(SmsSender::class, new FakeSmsSender);
    }

    /** Full customer login: request a code for the number, then submit it. */
    protected function loginWithOtp(string $phone, array $verifyPayload = []): TestResponse
    {
        $sms = $this->fakeSms();
        $this->post(route('login.otp'), ['phone' => $phone])->assertRedirect(route('login.verify'));

        return $this->post(route('login.verify'), ['code' => $sms->lastCode(), ...$verifyPayload]);
    }

    protected function seedSettings(array $overrides = []): void
    {
        $this->seed(StoreSettingSeeder::class);
        app(StoreSettings::class)->set(['min_order_value' => '0', 'delivery_fee' => '20', 'free_delivery_above' => '300', ...$overrides]);
    }

    protected function product(string $stock = '10', string $price = '50.00', array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test', 'is_active' => true]);

        return Product::factory()->for($category)->stock($stock)->create(['price' => $price, 'mrp' => null, ...$attributes])->fresh();
    }

    protected function addressPayload(array $overrides = []): array
    {
        return [
            'recipient_name' => 'Asha Verma',
            'phone' => '9876543210',
            'address_line' => '12 Station Road',
            'landmark' => 'Near temple',
            'locality' => 'Main Bazaar',
            'pincode' => '471201',
            'payment_method' => 'cod',
            ...$overrides,
        ];
    }

    protected function addToCart(User $customer, Product $product, string|int $quantity = 1): void
    {
        $this->actingAs($customer)
            ->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => $quantity])
            ->assertSessionHasNoErrors();
    }

    protected function placeOrder(User $customer, Product $product, string|int $quantity = 1, array $payload = []): Order
    {
        $this->addToCart($customer, $product, $quantity);

        $this->actingAs($customer)
            ->post(route('checkout.store'), $this->addressPayload($payload))
            ->assertSessionHasNoErrors();

        return Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
    }
}
