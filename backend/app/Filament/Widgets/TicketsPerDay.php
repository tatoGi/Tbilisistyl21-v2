<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\AdminOnlyWidget;
use App\Models\SoldTicket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TicketsPerDay extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?string $heading = 'Tickets Sold (Last 14 Days)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = SoldTicket::whereIn('status', ['paid', 'scanned'])
            ->where('paid_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(paid_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            $values[] = $data[$date] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tickets',
                    'data' => $values,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.6)',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
