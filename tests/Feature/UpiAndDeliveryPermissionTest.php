<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpiAndDeliveryPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings(['upi_id' => 'nowgongmart@okaxis']);
        $this->customer = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_upi_payment_stays_pending_until_admin_verifies(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1, ['payment_method' => 'upi', 'transaction_reference' => 'UTR123456789']);

        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('UTR123456789', $order->payment->transaction_reference);

        $this->actingAs($this->customer)->post(route('admin.payments.verify', $order->payment))->assertForbidden();
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);

        $this->actingAs($this->admin)->post(route('admin.payments.verify', $order->payment))->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame($this->admin->id, $order->payment->verified_by);

        $this->actingAs($this->admin)->post(route('admin.payments.reject', $order->payment), ['note' => 'oops'])->assertSessionHasErrors('payment');
    }

    public function test_admin_can_reject_upi_payment_with_reason(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1, ['payment_method' => 'upi']);

        $this->actingAs($this->admin)->post(route('admin.payments.reject', $order->payment), [])->assertSessionHasErrors('note');
        $this->actingAs($this->admin)->post(route('admin.payments.reject', $order->payment), ['note' => 'No credit in bank'])->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);
    }

    public function test_customer_can_add_upi_reference_after_ordering(): void
    {
        $order = $this->placeOrder($this->customer, $this->product('10'), 1, ['payment_method' => 'upi']);

        $this->actingAs($this->customer)->post(route('orders.upi-reference', $order), ['transaction_reference' => 'bad ref!'])->assertSessionHasErrors('transaction_reference');
        $this->actingAs($this->customer)->post(route('orders.upi-reference', $order), ['transaction_reference' => 'ABC123456'])->assertSessionHasNoErrors();

        $this->assertSame('ABC123456', $order->payment->fresh()->transaction_reference);
    }

    public function test_delivery_user_sees_only_assigned_orders(): void
    {
        $riderA = User::factory()->delivery()->create();
        $riderB = User::factory()->delivery()->create();
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.prepare', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $riderA->id]);

        $this->actingAs($riderA)->get(route('delivery.index'))->assertSee($order->order_number);
        $this->actingAs($riderA)->get(route('delivery.orders.show', $order))->assertOk();

        $this->actingAs($riderB)->get(route('delivery.index'))->assertDontSee($order->order_number);
        $this->actingAs($riderB)->get(route('delivery.orders.show', $order))->assertForbidden();
        $this->actingAs($riderB)->post(route('delivery.orders.pickup', $order))->assertForbidden();
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
    }

    public function test_delivery_user_cannot_use_admin_actions(): void
    {
        $rider = User::factory()->delivery()->create();
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);

        $this->actingAs($rider)->post(route('admin.orders.confirm', $order))->assertForbidden();
        $this->actingAs($rider)->post(route('admin.orders.cancel', $order), ['reason' => 'x'])->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_delivery_user_cannot_mark_delivered_before_pickup(): void
    {
        $rider = User::factory()->delivery()->create();
        $order = $this->placeOrder($this->customer, $this->product('10'), 1);
        $this->actingAs($this->admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.assign-delivery', $order), ['delivery_person_id' => $rider->id]);

        $this->actingAs($rider)->post(route('delivery.orders.delivered', $order), ['collected_amount' => '70'])->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }
}
