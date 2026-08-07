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
        // Apply payable surcharge after cache read so rate changes take effect
        // without busting the catalog cache.
        $rows = Cache::remember(Ticket::API_CACHE_KEY, 3600, function () {
            return Ticket::active()
                ->withCount(['soldTickets as sold' => fn ($q) => $q->where('status', 'paid')])
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get()
                ->toArray();
        });

        $surcharge = app(PaymentSurchargeService::class);

        return array_map(function (array $row) use ($surcharge) {
            $row['price_gel'] = $surcharge->payable((float) ($row['price_gel'] ?? 0));

            return $row;
        }, $rows);
    }

    public function findActive(string $id): ?Ticket
    {
        return Ticket::active()
            ->withCount(['soldTickets as sold' => fn ($q) => $q->where('status', 'paid')])
            ->find($id);
    }

    /** @deprecated Use findActive() for public API */
    public function find(string $id): ?Ticket
    {
        return $this->findActive($id);
    }

    public function clearCache(): void
    {
        Cache::forget(Ticket::API_CACHE_KEY);
    }
}
