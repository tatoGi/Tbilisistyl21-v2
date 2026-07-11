<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

class Ticket extends Model
{
    use HasUuids, HasTranslations;

    public const API_CACHE_KEY = 'tickets:active';

    public array $translatable = ['title', 'description', 'category', 'features'];

    protected $fillable = [
        'title', 'description', 'image', 'price_gel', 'quantity',
        'event_date', 'location', 'status', 'sale_url',
        'category', 'features', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'price_gel' => 'decimal:2',
            'is_featured' => 'boolean',
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

    /** Sold tickets that consumed capacity (paid). Used for availability %. */
    public function soldTickets(): HasMany
    {
        return $this->hasMany(SoldTicket::class, 'original_ticket_id');
    }
}
