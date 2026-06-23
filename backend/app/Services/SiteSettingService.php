<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingService
{
    public function all(): array
    {
        return Cache::remember('site-settings', 3600, function () {
            return SiteSetting::all()->pluck('value', 'key')->toArray();
        });
    }

    public function clearCache(): void
    {
        Cache::forget('site-settings');
    }
}
