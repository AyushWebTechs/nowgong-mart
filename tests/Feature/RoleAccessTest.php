<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    public function test_guest_is_redirected_to_login_from_private_areas(): void
    {
        foreach (['checkout.create', 'orders.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }

        foreach (['admin.dashboard', 'admin.orders.index', 'delivery.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('staff.login'));
        }
    }

    public function test_customer_cannot_open_admin_or_delivery(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('delivery.index'))->assertForbidden();
    }

    public function test_delivery_user_cannot_open_admin(): void
    {
        $delivery = User::factory()->delivery()->create();

        $this->actingAs($delivery)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($delivery)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($delivery)->get(route('delivery.index'))->assertOk();
    }

    public function test_admin_pages_render_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->product();

        foreach ([
            'admin.dashboard', 'admin.orders.index', 'admin.products.index', 'admin.products.create',
            'admin.categories.index', 'admin.categories.create', 'admin.inventory.index', 'admin.inventory.movements',
            'admin.delivery-users.index', 'admin.customers.index', 'admin.settings.edit', 'admin.reports.sales',
        ] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_only_customers_can_check_out(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('checkout.create'))->assertForbidden();
    }

    public function test_disabled_account_is_logged_out_on_next_request(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_storefront_pages_render(): void
    {
        $product = $this->product();

        $this->get(route('home'))->assertOk();
        $this->get(route('products.index', ['q' => $product->name]))->assertOk()->assertSee($product->name);
        $this->get(route('products.show', $product))->assertOk()->assertSee($product->name);
        $this->get(route('categories.show', $product->category))->assertOk();
        $this->get(route('cart.index'))->assertOk();
        $this->get(route('pages.show', 'privacy'))->assertOk();
        $this->get(route('home'))->assertDontSee('manifest.webmanifest', false)->assertDontSee('Install app');
    }
}
