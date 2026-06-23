<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class LocaleController extends Controller
{
    public function show()
    {
        return response()->json(['locale' => app()->getLocale()]);
    }
}
