<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Ticket extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'title', 'description', 'price_gel', 'quantity',
        'event_date', 'location', 'status', 'sale_url',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'price_gel' => 'decimal:2',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
