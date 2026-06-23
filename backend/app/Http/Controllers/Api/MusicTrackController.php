<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MusicTrack;
use Illuminate\Support\Facades\Cache;

class MusicTrackController extends Controller
{
    public function index()
    {
        $tracks = Cache::remember('music-tracks', 3600, function () {
            return MusicTrack::active()->ordered()->with('audioFile')->get();
        });
        return response()->json(['data' => $tracks]);
    }
}
