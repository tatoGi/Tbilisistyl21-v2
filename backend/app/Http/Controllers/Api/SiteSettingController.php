<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;

class SiteSettingController extends Controller
{
    public function __construct(private SiteSettingService $siteSettingService) {}

    public function index()
    {
        return response()->json(['data' => $this->siteSettingService->all()]);
    }
}
