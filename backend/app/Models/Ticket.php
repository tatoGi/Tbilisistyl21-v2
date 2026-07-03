<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

class Ticket extends Model
{
    use HasUuids, HasTranslations;

    public const API_CACHE_KEY = 'tickets:active';

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'title', 'description', 'image', 'price_gel', 'quantity',
        'event_date', 'location', 'status', 'sale_url',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'price_gel' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::API_CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
