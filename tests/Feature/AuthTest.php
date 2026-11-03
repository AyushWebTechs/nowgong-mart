<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OtpCode;
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

    public function test_new_number_signs_up_with_otp_and_is_verified(): void
    {
        $this->loginWithOtp('9876500000', ['name' => 'Ramesh', 'role' => 'admin'])->assertRedirect(route('home'));

        $user = User::where('phone', '9876500000')->firstOrFail();
        $this->assertSame('Ramesh', $user->name);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNotNull($user->last_login_at);
        $this->assertAuthenticatedAs($user);

        $otp = OtpCode::sole();
        $this->assertNotNull($otp->verified_at);
        $this->assertSame($user->id, $otp->user_id);
        $this->assertSame(1, $otp->attempts);
    }

    public function test_new_number_must_give_a_name(): void
    {
        $this->loginWithOtp('9876500000')->assertSessionHasErrors('name');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_customer_logs_in_with_otp(): void
    {
        $user = User::factory()->create(['phone' => '9876500002']);

        $this->get(route('login'))->assertOk()->assertSee('Send code');
        $this->loginWithOtp('9876500002')->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_wrong_codes_count_down_then_lock_the_code(): void
    {
        $sms = $this->fakeSms();
        User::factory()->create(['phone' => '9876500002']);
        $this->post(route('login.otp'), ['phone' => '9876500002']);
        $wrong = $sms->lastCode() === '000000' ? '111111' : '000000';

        $this->post(route('login.verify'), ['code' => $wrong])->assertSessionHasErrors(['code' => 'Incorrect code. 4 tries left.']);

        foreach (range(1, 4) as $ignored) {
            $this->post(route('login.verify'), ['code' => $wrong]);
        }

        // Five wrong tries burn the code, even the right one no longer works.
        $this->post(route('login.verify'), ['code' => $sms->lastCode()])->assertSessionHasErrors(['code' => 'This code has expired. Please request a new one.']);
        $this->assertGuest();
        $this->assertSame(5, OtpCode::sole()->attempts);
    }

    public function test_code_expires_and_cannot_be_reused(): void
    {
        $sms = $this->fakeSms();
        User::factory()->create(['phone' => '9876500002']);
        $this->post(route('login.otp'), ['phone' => '9876500002']);

        $this->travel(6)->minutes();
        $this->post(route('login.verify'), ['code' => $sms->lastCode()])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_resend_waits_and_is_capped_per_hour(): void
    {
        $sms = $this->fakeSms();

        $this->post(route('login.otp'), ['phone' => '9876500002'])->assertSessionHasNoErrors();
        $this->post(route('login.otp'), ['phone' => '9876500002'])->assertSessionHasErrors('phone');

        foreach (range(1, 4) as $ignored) {
            $this->travel(31)->seconds();
            $this->post(route('login.otp'), ['phone' => '9876500002'])->assertSessionHasNoErrors();
        }

        $this->travel(31)->seconds();
        $this->post(route('login.otp'), ['phone' => '9876500002'])->assertSessionHasErrors(['phone' => 'Too many codes requested for this number. Please try again in an hour.']);
        $this->assertCount(5, $sms->sent);
    }

    public function test_only_the_latest_code_works(): void
    {
        $sms = $this->fakeSms();
        User::factory()->create(['phone' => '9876500002']);

        $this->post(route('login.otp'), ['phone' => '9876500002']);
        $first = $sms->lastCode();
        $this->travel(31)->seconds();
        $this->post(route('login.otp'), ['phone' => '9876500002']);

        if ($first !== $sms->lastCode()) {
            $this->post(route('login.verify'), ['code' => $first])->assertSessionHasErrors('code');
        }
        $this->post(route('login.verify'), ['code' => $sms->lastCode()])->assertRedirect(route('home'));
    }

    public function test_verify_needs_a_code_requested_in_this_session(): void
    {
        $this->post(route('login.verify'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->get(route('login.verify'))->assertRedirect(route('login'));
    }

    public function test_invalid_numbers_staff_and_blocked_accounts_get_no_code(): void
    {
        $sms = $this->fakeSms();
        User::factory()->admin()->create(['phone' => '9876500005']);
        User::factory()->inactive()->create(['phone' => '9876500003']);

        $this->post(route('login.otp'), ['phone' => '12345'])->assertSessionHasErrors('phone');
        $this->post(route('login.otp'), ['phone' => '9876500005'])->assertSessionHasErrors(['phone' => 'This number belongs to a shop staff account. Please use staff login.']);
        $this->post(route('login.otp'), ['phone' => '9876500003'])->assertSessionHasErrors(['phone' => 'This account is blocked. Please contact the shop.']);

        $this->assertSame([], $sms->sent);
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_sms_failure_is_reported_and_not_counted(): void
    {
        $sms = $this->fakeSms();
        $sms->fail = true;

        $this->post(route('login.otp'), ['phone' => '9876500002'])->assertSessionHasErrors(['phone' => 'We could not send the SMS right now. Please try again in a minute.']);
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_demo_mode_shows_the_code_on_screen(): void
    {
        $sms = $this->fakeSms();

        $this->post(route('login.otp'), ['phone' => '9876500002']);
        $this->get(route('login.verify'))->assertOk()->assertDontSee('Demo mode')->assertSee('Verify & create account');

        config(['otp.demo' => true]);
        $this->travel(31)->seconds();
        $this->followingRedirects()->post(route('login.otp'), ['phone' => '9876500002'])->assertSee('Demo mode')->assertSee($sms->lastCode());
    }

    public function test_register_page_redirects_to_otp_login(): void
    {
        $this->get('/register')->assertRedirect('/login');
    }

    public function test_staff_log_in_with_password_and_land_on_their_dashboards(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '9876500005']);
        $this->post(route('staff.login'), ['phone' => '9876500005', 'password' => 'wrong'])->assertSessionHasErrors('phone');
        $this->post(route('staff.login'), ['phone' => '9876500005', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull($admin->fresh()->last_login_at);

        $this->post(route('logout'));

        User::factory()->delivery()->create(['phone' => '9876500006']);
        $this->post(route('staff.login'), ['phone' => '9876500006', 'password' => 'password'])->assertRedirect(route('delivery.index'));
    }

    public function test_customers_and_inactive_staff_cannot_use_password_login(): void
    {
        User::factory()->create(['phone' => '9876500002']);
        User::factory()->delivery()->inactive()->create(['phone' => '9876500003']);

        $this->post(route('staff.login'), ['phone' => '9876500002', 'password' => 'password'])->assertSessionHasErrors('phone');
        $this->post(route('staff.login'), ['phone' => '9876500003', 'password' => 'password'])->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_staff_login_is_throttled(): void
    {
        User::factory()->admin()->create(['phone' => '9876500004']);

        foreach (range(1, 5) as $ignored) {
            $this->post(route('staff.login'), ['phone' => '9876500004', 'password' => 'wrong']);
        }

        $this->post(route('staff.login'), ['phone' => '9876500004', 'password' => 'password'])->assertStatus(429);
    }

    public function test_admin_sees_login_history(): void
    {
        $this->loginWithOtp('9876500000', ['name' => 'Ramesh']);
        $this->post(route('logout'));

        $this->actingAs(User::factory()->create())->get(route('admin.login-history.index'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.login-history.index', ['q' => '98765']))
            ->assertOk()
            ->assertSee('9876500000')
            ->assertSee('Ramesh')
            ->assertSee('Verified');

        $this->get(route('admin.customers.index'))->assertOk()->assertSee('✓ Verified', false);
    }
}
