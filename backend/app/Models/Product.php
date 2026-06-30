<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasUuids, HasTranslations;

    public const API_CACHE_KEY = 'products:active';

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'title', 'description', 'price_gel', 'category',
        'is_vip', 'image_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price_gel' => 'decimal:2',
            'is_vip' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::API_CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
