<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Sms\Fast2SmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwoFactorSmsSender;
use App\Services\StoreSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(StoreSettings::class);
        $this->app->scoped(CartService::class);

        $this->app->bind(SmsSender::class, fn () => match (config('otp.driver')) {
            '2factor' => new TwoFactorSmsSender((string) config('otp.2factor.key'), config('otp.2factor.template')),
            'fast2sms' => new Fast2SmsSender((string) config('otp.fast2sms.key')),
            default => new LogSmsSender,
        });
    }

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('phone')).'|'.$request->ip()));
        RateLimiter::for('otp-send', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        View::composer('*', function ($view) {
            $view->with('store', app(StoreSettings::class));
        });

        // Cart snapshot for the header badge, floating cart bar and ADD/stepper buttons on product cards.
        View::composer(['layouts.app', 'partials.product-card', 'partials.add-to-cart'], function ($view) {
            $cart = app(CartService::class)->state();
            $view->with(['cartState' => $cart, 'cartCount' => $cart['count']]);
        });

        View::composer('partials.search', function ($view) {
            $view->with([
                'navCategories' => Category::active()->whereNull('parent_id')->ordered()->get(),
                // Best sellers (featured first) as one-tap "trending" searches; refreshed every 10 minutes.
                'trendingSearches' => Cache::remember('search:trending', now()->addMinutes(10), fn () => Product::active()
                    ->withSum('orderItems as sold_qty', 'quantity')
                    ->orderByDesc('is_featured')
                    ->orderByDesc('sold_qty')
                    ->take(6)
                    ->pluck('name')
                    ->map(fn ($name) => Str::of($name)->before(' - ')->words(3, '')->trim()->value())
                    ->unique()
                    ->values()
                    ->all()),
            ]);
        });

        // Turbo reloads the page fully when the asset build changes.
        Vite::useScriptTagAttributes(['data-turbo-track' => 'reload']);
        Vite::useStyleTagAttributes(['data-turbo-track' => 'reload']);
    }
}
