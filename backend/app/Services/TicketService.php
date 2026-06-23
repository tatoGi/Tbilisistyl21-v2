<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    public function listActive(): Collection
    {
        return Cache::remember('tickets:active', 3600, function () {
            return Ticket::active()->get();
        });
    }

    public function find(string $id): ?Ticket
    {
        return Ticket::find($id);
    }

    public function clearCache(): void
    {
        Cache::forget('tickets:active');
    }
}
