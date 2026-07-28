<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $appUrl = config('app.url', '');
        if (
            app()->environment('production')
            && is_string($appUrl)
            && str_starts_with($appUrl, 'https://')
        ) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('orders', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('payments', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('personal-number', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('votes', function ($request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Livewire/Filament admin uploads expect this directory on the default disk.
        $livewireTmp = storage_path('app/private/livewire-tmp');
        if (! is_dir($livewireTmp)) {
            mkdir($livewireTmp, 0775, true);
        }
    }
}
