<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ProductSize extends Model
{
    protected $fillable = ['product_id', 'size', 'quantity'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(Product::API_CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
