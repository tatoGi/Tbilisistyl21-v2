<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;

class PartnerController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Partner::orderBy('order')->with('logo')->get()]);
    }
}
