<?php

namespace App\Providers;

use App\Services\CartService;
use App\Services\StoreSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(StoreSettings::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('phone')).'|'.$request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        View::composer('*', function ($view) {
            $view->with('store', app(StoreSettings::class));
        });

        View::composer('layouts.app', function ($view) {
            $view->with('cartCount', app(CartService::class)->count());
        });
    }
}
