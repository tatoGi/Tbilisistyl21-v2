<?php

namespace App\Services;

use App\Models\ProductOrder;
use App\Models\SoldTicket;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountingReportService
{
    public const BANK_FEE_RATE = 0.025;

    public function summary(Carbon $from, Carbon $to, string $channel): array
    {
        $rows = $this->loadRows($from, $to, $channel);

        $gross = round($rows->sum(fn (array $row) => $row['amount']), 2);
        $base = round($rows->sum(fn (array $row) => $row['base_amount']), 2);
        $surcharge = round($rows->sum(fn (array $row) => $row['surcharge_amount']), 2);
        $bankFee = round($rows->sum(fn (array $row) => $row['estimated_bank_fee']), 2);

        return [
            'gross' => $gross,
            'base' => $base,
            'surcharge' => $surcharge,
            'estimated_bank_fee' => $bankFee,
            'estimated_net' => round($gross - $bankFee, 2),
            'ticket_count' => $rows->where('type', 'ticket')->count(),
            'product_count' => $rows->where('type', 'product')->count(),
        ];
    }

    public function breakdownByKind(Carbon $from, Carbon $to, string $channel): array
    {
        $rows = $this->loadRows($from, $to, $channel);

        return [
            'tickets' => $this->aggregateGroup($rows->where('type', 'ticket')),
            'products' => $this->aggregateGroup($rows->where('type', 'product')),
        ];
    }

    public function breakdownByTicketType(Carbon $from, Carbon $to, string $channel): array
    {
        $tickets = $this->loadRows($from, $to, $channel)->where('type', 'ticket');

        return [
            'joker' => $this->aggregateGroup($tickets->where('ticket_type', 'joker')),
            'techno' => $this->aggregateGroup($tickets->where('ticket_type', 'techno')),
            'standard' => $this->aggregateGroup($tickets->where('ticket_type', 'standard')),
        ];
    }

    /**
     * @return list<array{date: string, gross: float, count: int}>
     */
    public function breakdownByDay(Carbon $from, Carbon $to, string $channel): array
    {
        return $this->loadRows($from, $to, $channel)
            ->groupBy('date')
            ->sortKeys()
            ->map(fn (Collection $dayRows, string $date) => [
                'date' => $date,
                'gross' => round($dayRows->sum(fn (array $row) => $row['amount']), 2),
                'count' => $dayRows->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public function csvRows(Carbon $from, Carbon $to, string $channel): iterable
    {
        foreach ($this->loadRows($from, $to, $channel) as $row) {
            yield [
                'type' => $row['type'],
                'id' => $row['id'],
                'paid_at' => $row['paid_at'],
                'channel' => $row['channel'],
                'title' => $row['title'],
                'base_amount' => $row['base_amount'],
                'surcharge_rate' => $row['surcharge_rate'],
                'surcharge_amount' => $row['surcharge_amount'],
                'amount' => $row['amount'],
                'estimated_bank_fee' => $row['estimated_bank_fee'],
                'email' => $row['email'],
                'sold_by' => $row['sold_by'],
            ];
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function loadRows(Carbon $from, Carbon $to, string $channel): Collection
    {
        $tickets = SoldTicket::query()
            ->where('status', 'paid')
            ->get()
            ->map(function (SoldTicket $ticket) {
                $paidAt = $ticket->paid_at ?? $ticket->created_at;
                $soldBy = $ticket->sold_by;
                $isOnline = empty($soldBy);

                return [
                    'type' => 'ticket',
                    'id' => $ticket->id,
                    'paid_at' => $paidAt?->format('Y-m-d H:i:s'),
                    'paid_at_carbon' => $paidAt,
                    'date' => $paidAt?->toDateString(),
                    'channel' => $isOnline ? 'online' : 'walk_up',
                    'title' => $ticket->event_name,
                    'base_amount' => round((float) ($ticket->base_amount ?? $ticket->amount), 2),
                    'surcharge_rate' => $ticket->surcharge_rate !== null ? round((float) $ticket->surcharge_rate, 2) : null,
                    'surcharge_amount' => round((float) ($ticket->surcharge_amount ?? 0), 2),
                    'amount' => round((float) $ticket->amount, 2),
                    'estimated_bank_fee' => $this->estimatedBankFee($ticket),
                    'email' => $ticket->email,
                    'sold_by' => $soldBy,
                    'ticket_type' => $this->ticketType($ticket),
                ];
            });

        $products = ProductOrder::query()
            ->where('status', 'paid')
            ->get()
            ->map(function (ProductOrder $order) {
                $paidAt = $order->paid_at;
                $soldBy = $order->sold_by;
                $isOnline = empty($soldBy);

                return [
                    'type' => 'product',
                    'id' => $order->id,
                    'paid_at' => $paidAt?->format('Y-m-d H:i:s'),
                    'paid_at_carbon' => $paidAt,
                    'date' => $paidAt?->toDateString(),
                    'channel' => $isOnline ? 'online' : 'walk_up',
                    'title' => $order->product_title,
                    'base_amount' => round((float) ($order->base_amount ?? $order->amount), 2),
                    'surcharge_rate' => $order->surcharge_rate !== null ? round((float) $order->surcharge_rate, 2) : null,
                    'surcharge_amount' => round((float) ($order->surcharge_amount ?? 0), 2),
                    'amount' => round((float) $order->amount, 2),
                    'estimated_bank_fee' => $this->estimatedBankFee($order),
                    'email' => $order->email,
                    'sold_by' => $soldBy,
                    'ticket_type' => null,
                ];
            });

        return $tickets
            ->concat($products)
            ->filter(function (array $row) use ($from, $to, $channel) {
                /** @var Carbon|null $paidAt */
                $paidAt = $row['paid_at_carbon'];
                if ($paidAt === null) {
                    return false;
                }

                if ($paidAt->lt($from) || $paidAt->gt($to)) {
                    return false;
                }

                return match ($channel) {
                    'online' => $row['channel'] === 'online',
                    'walk_up' => $row['channel'] === 'walk_up',
                    default => true,
                };
            })
            ->values();
    }

    private function estimatedBankFee(object $row): float
    {
        if (! empty($row->sold_by)) {
            return 0.0;
        }

        return round((float) $row->amount * self::BANK_FEE_RATE, 2);
    }

    private function ticketType(SoldTicket $ticket): string
    {
        if ($ticket->isJokerEvent()) {
            return 'joker';
        }

        if ($ticket->isTechnoEvent()) {
            return 'techno';
        }

        return 'standard';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{gross: float, base: float, surcharge: float, count: int}
     */
    private function aggregateGroup(Collection $rows): array
    {
        return [
            'gross' => round($rows->sum(fn (array $row) => $row['amount']), 2),
            'base' => round($rows->sum(fn (array $row) => $row['base_amount']), 2),
            'surcharge' => round($rows->sum(fn (array $row) => $row['surcharge_amount']), 2),
            'count' => $rows->count(),
        ];
    }
}
