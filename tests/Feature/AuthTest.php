<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    public function test_customer_can_register_and_is_logged_in_as_customer(): void
    {
        $this->post(route('register'), [
            'name' => 'Ramesh',
            'phone' => '9876500000',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])->assertRedirect(route('home'));

        $user = User::where('phone', '9876500000')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_ignores_a_submitted_role(): void
    {
        $this->post(route('register'), [
            'name' => 'Sneaky',
            'phone' => '9876500001',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Customer, User::where('phone', '9876500001')->first()->role);
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['phone' => '9876500002']);

        $this->post(route('login'), ['phone' => '9876500002', 'password' => 'wrong'])->assertSessionHasErrors('phone');
        $this->assertGuest();

        $this->post(route('login'), ['phone' => '9876500002', 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create(['phone' => '9876500003']);

        $this->post(route('login'), ['phone' => '9876500003', 'password' => 'password'])->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_login_is_throttled(): void
    {
        User::factory()->create(['phone' => '9876500004']);

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login'), ['phone' => '9876500004', 'password' => 'wrong']);
        }

        $this->post(route('login'), ['phone' => '9876500004', 'password' => 'password'])->assertStatus(429);
    }

    public function test_admin_and_delivery_are_sent_to_their_dashboards(): void
    {
        User::factory()->admin()->create(['phone' => '9876500005']);
        $this->post(route('login'), ['phone' => '9876500005', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'));

        User::factory()->delivery()->create(['phone' => '9876500006']);
        $this->post(route('login'), ['phone' => '9876500006', 'password' => 'password'])->assertRedirect(route('delivery.index'));
    }
}
