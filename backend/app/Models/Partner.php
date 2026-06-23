<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Partner extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['description'];

    protected $fillable = ['name', 'description', 'logo_id', 'url', 'order'];

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }
}
