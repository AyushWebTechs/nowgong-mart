<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private User $rider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
        $this->customer = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->rider = User::factory()->delivery()->create();
    }

    public function test_full_cod_delivery_flow(): void
    {
        $product = $this->product('10', '100.00');
        $order = $this->placeOrder($this->customer, $product, 2);

        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.orders.prepare', $order))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $this->rider->id])->assertSessionHasNoErrors();
        $this->actingAs($this->rider)->post(route('delivery.orders.pickup', $order))->assertSessionHasNoErrors();
        $this->actingAs($this->rider)->post(route('delivery.orders.delivered', $order), ['collected_amount' => '220'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNotNull($order->delivered_at);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame('220.00', $order->payment->collected_amount);
        $this->assertSame($this->rider->id, $order->payment->collected_by);
        $this->assertSame(6, $order->statusHistory()->count());

        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->order_number);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee($order->order_number);
        $this->actingAs($this->admin)->get(route('admin.reports.sales'))->assertOk()->assertSee('₹220.00');
        $this->actingAs($this->admin)->get(route('admin.inventory.movements', ['product_id' => $product->id]))->assertOk()->assertSee($order->order_number);
        $this->actingAs($this->rider)->get(route('delivery.index'))->assertOk()->assertSee('Delivered today');
        $this->actingAs($this->customer)->get(route('orders.index'))->assertOk()->assertSee($order->order_number);
    }

    public function test_cod_cash_mismatch_stays_pending_for_admin_review(): void
    {
        $order = $this->deliverableOrder();

        $this->actingAs($this->rider)->post(route('delivery.orders.delivered', $order), ['collected_amount' => '100'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertTrue($order->payment->hasCollectionMismatch());
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);

        $this->actingAs($this->admin)->post(route('admin.orders.prepare', $order))->assertSessionHasErrors('status');
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $this->rider->id])->assertSessionHasErrors('delivery_person_id');
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_cannot_assign_a_non_delivery_user(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));

        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $this->customer->id])
            ->assertSessionHasErrors('delivery_person_id');
    }

    public function test_pickup_requires_preparing_status(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $this->rider->id]);

        $this->actingAs($this->rider)->post(route('delivery.orders.pickup', $order))->assertSessionHasErrors('status');
    }

    public function test_cancellation_restores_stock_exactly_once(): void
    {
        $product = $this->product('10');
        $order = $this->placeOrder($this->customer, $product, 4);
        $this->assertSame('6.000', $product->fresh()->stock_qty);

        $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), ['reason' => 'Customer asked'])->assertSessionHasNoErrors();
        $this->assertSame('10.000', $product->fresh()->stock_qty);

        $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), ['reason' => 'again'])->assertSessionHasErrors('status');
        $this->actingAs($this->customer)->post(route('orders.cancel', $order))->assertSessionHasErrors('status');

        $this->assertSame('10.000', $product->fresh()->stock_qty);
        $this->assertSame(1, InventoryMovement::where('order_id', $order->id)->where('type', MovementType::OrderCancelled)->count());
        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);
    }

    public function test_customer_can_cancel_only_while_pending(): void
    {
        $product = $this->product('10');
        $first = $this->placeOrder($this->customer, $product, 1);
        $this->actingAs($this->customer)->post(route('orders.cancel', $first))->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Cancelled, $first->fresh()->status);

        $second = $this->placeOrder($this->customer, $product, 1);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $second));
        $this->actingAs($this->customer)->post(route('orders.cancel', $second))->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Confirmed, $second->fresh()->status);
    }

    public function test_customer_cannot_cancel_someone_elses_order(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $other = User::factory()->create();

        $this->actingAs($other)->post(route('orders.cancel', $order))->assertNotFound();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_admin_can_run_the_whole_flow_without_a_rider(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10', '100.00'), 2);

        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.orders.prepare', $order))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.orders.dispatch', $order))->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);
        $this->assertNull($order->fresh()->delivery_person_id);

        $this->actingAs($this->customer)->get(route('orders.show', $order))
            ->assertSee('Your order is on the way!')
            ->assertSee('The shop is delivering your order')
            ->assertSee('data-live-refresh', false);

        $this->actingAs($this->admin)->post(route('admin.orders.deliver', $order), ['collected_amount' => '220'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame($this->admin->id, $order->payment->collected_by);
        $this->assertSame('Marked delivered by the shop', $order->statusHistory->last()->note);

        $this->actingAs($this->customer)->get(route('orders.show', $order))
            ->assertSee('Delivered. Enjoy!')
            ->assertSee($order->delivered_at->format('d M, h:i A'))
            ->assertDontSee('data-live-refresh', false);
    }

    public function test_admin_dispatch_with_a_rider_assigns_them(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.prepare', $order));

        $this->actingAs($this->admin)->post(route('admin.orders.dispatch', $order), ['delivery_person_id' => $this->rider->id])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::OutForDelivery, $order->status);
        $this->assertSame($this->rider->id, $order->delivery_person_id);
        $this->assertSame("Out with {$this->rider->name}", $order->statusHistory->last()->note);

        $this->actingAs($this->customer)->get(route('orders.show', $order))->assertSee($this->rider->name)->assertSee("tel:+91{$this->rider->phone}", false);

        // The rider can still finish it from their app.
        $this->actingAs($this->rider)->post(route('delivery.orders.delivered', $order), ['collected_amount' => '70'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_admin_cannot_skip_steps(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);

        $this->actingAs($this->admin)->post(route('admin.orders.dispatch', $order))->assertSessionHasErrors('status');
        $this->actingAs($this->admin)->post(route('admin.orders.deliver', $order), ['collected_amount' => '70'])->assertSessionHasErrors('status');
        $this->actingAs($this->rider)->post(route('admin.orders.deliver', $order), ['collected_amount' => '70'])->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_tracking_page_shows_when_each_step_happened(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $this->travel(5)->minutes();
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $this->rider->id]);

        $times = $order->fresh()->load('statusHistory')->statusTimes();
        $this->assertSame(['pending', 'confirmed'], array_keys($times)); // the assignment note isn't a step

        $this->actingAs($this->customer)->get(route('orders.show', $order))
            ->assertSee('The shop has accepted your order.')
            ->assertSee($times['confirmed']->format('d M, h:i A'));
    }

    private function deliverableOrder()
    {
        $order = $this->placeOrder($this->customer, $this->product('10', '100.00'), 2);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.prepare', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $this->rider->id]);
        $this->actingAs($this->rider)->post(route('delivery.orders.pickup', $order));

        return $order;
    }
}
