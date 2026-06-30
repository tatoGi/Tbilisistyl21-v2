<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MusicTrack;
use Illuminate\Support\Facades\Cache;

class MusicTrackController extends Controller
{
    public function index()
    {
        $tracks = Cache::remember(MusicTrack::API_CACHE_KEY, 3600, function () {
            return MusicTrack::active()
                ->ordered()
                ->with('audioFile')
                ->get()
                ->map(fn (MusicTrack $track) => [
                    'id' => $track->id,
                    'title' => $track->getTranslations('title'),
                    'artist' => $track->artist,
                    'audio_file_id' => $track->audio_file_id,
                    'order' => $track->order,
                    'status' => $track->status,
                    'audio_file' => $track->audioFile ? [
                        'id' => $track->audioFile->id,
                        'filename' => $track->audioFile->filename,
                        'path' => $track->audioFile->path,
                        'mime_type' => $track->audioFile->mime_type,
                        'size' => $track->audioFile->size,
                        'alt' => $track->audioFile->alt,
                    ] : null,
                ])
                ->values()
                ->all();
        });

        return response()->json(['data' => $tracks]);
    }
}
