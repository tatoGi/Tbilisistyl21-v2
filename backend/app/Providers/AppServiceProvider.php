<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('orders', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('payments', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
