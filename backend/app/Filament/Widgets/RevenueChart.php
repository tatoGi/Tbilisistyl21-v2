<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\AdminOnlyWidget;
use App\Models\SoldTicket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?string $heading = 'Daily Revenue (Last 30 Days)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = SoldTicket::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            $values[] = $data[$date] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (GEL)',
                    'data' => $values,
                    'borderColor' => '#f5a623',
                    'backgroundColor' => 'rgba(245, 166, 35, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
