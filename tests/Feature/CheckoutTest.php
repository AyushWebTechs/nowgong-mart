<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
        $this->customer = User::factory()->create();
    }

    public function test_cod_order_is_created_with_server_side_totals_and_stock_deduction(): void
    {
        $product = $this->product('10', '45.50');

        $order = $this->placeOrder($this->customer, $product, 3);

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('136.50', $order->subtotal);
        $this->assertSame('20.00', $order->delivery_fee);
        $this->assertSame('156.50', $order->total);
        $this->assertSame('7.000', $product->fresh()->stock_qty);
        $this->assertDatabaseHas('inventory_movements', ['order_id' => $order->id, 'type' => MovementType::OnlineOrder->value, 'quantity_change' => '-3.000']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => '156.50', 'status' => 'pending']);
        $this->assertSame(0, CartItem::count());
    }

    public function test_free_delivery_above_threshold(): void
    {
        $product = $this->product('10', '150.00');

        $order = $this->placeOrder($this->customer, $product, 2);

        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('300.00', $order->total);
    }

    public function test_price_tampering_is_ignored(): void
    {
        $product = $this->product('10', '100.00');
        $this->addToCart($this->customer, $product, 1);

        $this->actingAs($this->customer)->post(route('checkout.store'), $this->addressPayload([
            'price' => '1', 'unit_price' => '1', 'subtotal' => '1', 'total' => '1', 'delivery_fee' => '0', 'discount' => '99',
        ]))->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame('100.00', $order->subtotal);
        $this->assertSame('120.00', $order->total);
        $this->assertSame('0.00', $order->discount);
        $this->assertSame('100.00', $order->items->first()->unit_price);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = $this->product('2');

        $this->actingAs($this->customer)
            ->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, CartItem::count());
    }

    public function test_fractional_quantity_only_for_loose_units(): void
    {
        $packet = $this->product('10');
        $loose = $this->product('10', '40.00', ['unit' => 'kg']);

        $this->actingAs($this->customer)->post(route('cart.items.store'), ['product_id' => $packet->id, 'quantity' => '1.5'])->assertSessionHasErrors('quantity');
        $this->actingAs($this->customer)->post(route('cart.items.store'), ['product_id' => $loose->id, 'quantity' => '1.5'])->assertSessionHasNoErrors();
    }

    public function test_two_customers_cannot_both_buy_the_last_unit(): void
    {
        $product = $this->product('1');
        $second = User::factory()->create();

        $this->addToCart($this->customer, $product, 1);
        $this->addToCart($second, $product, 1);

        $this->actingAs($this->customer)->post(route('checkout.store'), $this->addressPayload())->assertSessionHasNoErrors();
        $this->actingAs($second)->post(route('checkout.store'), $this->addressPayload())->assertSessionHasErrors('cart');

        $this->assertSame(1, Order::count());
        $this->assertSame('0.000', $product->fresh()->stock_qty);
    }

    public function test_order_keeps_product_snapshot_after_catalogue_changes(): void
    {
        $product = $this->product('10', '30.00');
        $order = $this->placeOrder($this->customer, $product, 1);

        $original = $product->name;
        $product->update(['name' => 'Renamed product', 'price' => '99.00', 'unit' => 'kg']);

        $item = $order->items()->first();
        $this->assertSame($original, $item->product_name);
        $this->assertSame('30.00', $item->unit_price);
        $this->assertSame('pcs', $item->unit);
    }

    public function test_inactive_product_in_cart_blocks_checkout(): void
    {
        $product = $this->product('10');
        $this->addToCart($this->customer, $product, 1);
        $product->update(['is_active' => false]);

        $this->actingAs($this->customer)->post(route('checkout.store'), $this->addressPayload())->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
    }

    public function test_minimum_order_value_is_enforced(): void
    {
        app(StoreSettings::class)->set(['min_order_value' => '200']);
        $product = $this->product('10', '50.00');
        $this->addToCart($this->customer, $product, 1);

        $this->actingAs($this->customer)->post(route('checkout.store'), $this->addressPayload())->assertSessionHasErrors('cart');
        $this->assertSame('10.000', $product->fresh()->stock_qty);
    }

    public function test_delivery_outside_service_pincode_is_rejected(): void
    {
        $product = $this->product('10');
        $this->addToCart($this->customer, $product, 1);

        $this->actingAs($this->customer)->post(route('checkout.store'), $this->addressPayload(['pincode' => '110001']))->assertSessionHasErrors('pincode');
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_blocked_when_store_closed(): void
    {
        app(StoreSettings::class)->set(['accepting_orders' => '0']);
        $product = $this->product('10');
        $this->addToCart($this->customer, $product, 1);

        $this->actingAs($this->customer)->post(route('checkout.store'), $this->addressPayload())->assertSessionHasErrors('cart');
    }

    public function test_saved_address_of_another_user_cannot_be_used(): void
    {
        $other = User::factory()->create();
        $address = $other->addresses()->create(collect($this->addressPayload())->except('payment_method')->all());
        $product = $this->product('10');
        $this->addToCart($this->customer, $product, 1);

        $this->actingAs($this->customer)->post(route('checkout.store'), ['address_id' => $address->id, 'payment_method' => 'cod'])
            ->assertSessionHasErrors('address_id');
    }

    public function test_guest_cart_merges_into_customer_cart_on_login(): void
    {
        $product = $this->product('10');
        $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2])->assertSessionHasNoErrors();

        $this->post(route('login'), ['phone' => $this->customer->phone, 'password' => 'password']);

        $this->assertSame('2.000', $this->customer->cart->items()->first()->quantity);
    }

    public function test_customer_cannot_modify_another_cart(): void
    {
        $product = $this->product('10');
        $other = User::factory()->create();
        $this->addToCart($other, $product, 1);
        $item = CartItem::firstOrFail();

        $this->actingAs($this->customer)->patch(route('cart.items.update', $item), ['quantity' => 5])->assertNotFound();
        $this->actingAs($this->customer)->delete(route('cart.items.destroy', $item))->assertNotFound();
        $this->assertSame('1.000', $item->fresh()->quantity);
    }

    public function test_customer_can_only_see_own_orders(): void
    {
        $product = $this->product('10');
        $order = $this->placeOrder($this->customer, $product, 1);
        $other = User::factory()->create();

        $this->actingAs($this->customer)->get(route('orders.show', $order))->assertOk()->assertSee($order->order_number);
        $this->actingAs($other)->get(route('orders.show', $order))->assertNotFound();
    }
}
