<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocaleFromHeader
{
    private const SUPPORTED = ['ka', 'en', 'ru', 'ua'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', 'ka');
        $locale = in_array($locale, self::SUPPORTED, true) ? $locale : 'ka';
        app()->setLocale($locale);

        return $next($request);
    }
}
