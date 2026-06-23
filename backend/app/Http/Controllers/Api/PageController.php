<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->first();
        if (!$page) {
            return response()->json(['error' => 'not_found'], 404);
        }
        return response()->json(['data' => $page]);
    }
}
