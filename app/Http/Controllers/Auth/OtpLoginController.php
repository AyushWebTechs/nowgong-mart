<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use App\Services\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Customer sign-up and login in one flow: mobile number -> SMS code -> logged in. */
class OtpLoginController extends Controller
{
    private const SESSION_PHONE = 'otp.phone';

    public function __construct(private OtpService $otp) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function send(Request $request): RedirectResponse
    {
        $phone = $request->validate(
            ['phone' => ['required', 'regex:/^[6-9]\d{9}$/']],
            ['phone.regex' => 'Enter a valid 10-digit mobile number.'],
        )['phone'];

        $this->ensureCustomerNumber(User::where('phone', $phone)->first(), 'phone');

        $code = $this->otp->send($phone, $request->ip(), $request->userAgent());

        $request->session()->put(self::SESSION_PHONE, $phone);
        if (config('otp.demo')) {
            $request->session()->flash('otp.demo_code', $code);
        }

        return redirect()->route('login.verify');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_PHONE);
        if (! $phone) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp', [
            'phone' => $phone,
            'isNew' => ! User::where('phone', $phone)->exists(),
            'demoCode' => $request->session()->get('otp.demo_code'),
            'resendIn' => $this->otp->secondsUntilResend($this->otp->latestFor($phone)),
        ]);
    }

    public function verify(Request $request, CartService $cart): RedirectResponse
    {
        // The number comes from the session, never the form, so a verified code can't be reused for another number.
        $phone = $request->session()->get(self::SESSION_PHONE);
        if (! $phone) {
            return redirect()->route('login');
        }

        $user = User::where('phone', $phone)->first();
        $data = $request->validate([
            'code' => ['required', 'digits:'.config('otp.length')],
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:100'],
        ], ['code.digits' => 'Enter the '.config('otp.length').'-digit code.', 'name.required' => 'Tell us your name to create your account.']);

        $this->ensureCustomerNumber($user, 'code');
        $otp = $this->otp->verify($phone, $data['code']);

        if (! $user) {
            $user = new User(['name' => $data['name'], 'phone' => $phone, 'password' => Hash::make(Str::random(40))]);
            $user->forceFill(['role' => UserRole::Customer, 'is_active' => true]);
        }

        $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?? now(), 'last_login_at' => now()])->save();
        $otp->update(['user_id' => $user->id]);

        $guestCartKey = $request->session()->get(CartService::SESSION_KEY);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget([self::SESSION_PHONE, CartService::SESSION_KEY]);

        $cart->mergeGuestCart($user, $guestCartKey);

        return redirect()->intended(route('home'))->with('success', $user->wasRecentlyCreated ? "Welcome to {$this->storeName()}, {$user->name}!" : "Welcome back, {$user->name}!");
    }

    /** Staff accounts never log in by SMS code; blocked customers can't log in at all. */
    private function ensureCustomerNumber(?User $user, string $field): void
    {
        if ($user && ! $user->isCustomer()) {
            throw ValidationException::withMessages([$field => 'This number belongs to a shop staff account. Please use staff login.']);
        }

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([$field => 'This account is blocked. Please contact the shop.']);
        }
    }

    private function storeName(): string
    {
        return app(StoreSettings::class)->get('store_name');
    }
}
