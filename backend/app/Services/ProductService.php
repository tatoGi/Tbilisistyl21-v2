<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActive(): array
    {
        // Cache the plain array, not the Eloquent Collection: serialized models
        // contain NUL bytes that don't round-trip through the Postgres `text`
        // cache column, so a cached Collection deserializes as an incomplete
        // class. toArray() preserves the same JSON shape (full translations +
        // nested image/sizes). Mirrors the MusicTrackController approach.
        return Cache::remember(Product::API_CACHE_KEY, 3600, function () {
            return Product::active()
                ->with(['sizes', 'image'])
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get()
                ->toArray();
        });
    }

    public function findActive(string $id): ?Product
    {
        return Product::active()->with(['sizes', 'image'])->find($id);
    }

    /** @deprecated Use findActive() for public API */
    public function find(string $id): ?Product
    {
        return $this->findActive($id);
    }

    public function clearCache(): void
    {
        Cache::forget('products:active');
    }
}
