<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function listActive(): Collection
    {
        return Cache::remember('products:active', 3600, function () {
            return Product::active()->with(['sizes', 'image'])->get();
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
