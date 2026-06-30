<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActive(): array
    {
        // Cache a plain array, not the Eloquent Collection: serialized models
        // contain NUL bytes that don't round-trip through the Postgres `text`
        // cache column (a cached Collection deserializes as an incomplete class).
        return Cache::remember('tickets:active', 3600, function () {
            return Ticket::active()->get()->toArray();
        });
    }

    public function findActive(string $id): ?Ticket
    {
        return Ticket::active()->find($id);
    }

    /** @deprecated Use findActive() for public API */
    public function find(string $id): ?Ticket
    {
        return $this->findActive($id);
    }

    public function clearCache(): void
    {
        Cache::forget('tickets:active');
    }
}
