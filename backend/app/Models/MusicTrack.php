<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class MusicTrack extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = ['title', 'artist', 'audio_file_id', 'order', 'status'];

    public function audioFile(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_file_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }
}
