<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\AdminOnlyWidget;
use App\Models\ProductOrder;
use App\Models\SoldTicket;
use Filament\Widgets\ChartWidget;

class RevenueSplit extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?string $heading = 'Revenue Split';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $tickets = (float) SoldTicket::whereIn('status', ['paid', 'scanned'])->sum('amount');
        $products = (float) ProductOrder::where('status', 'paid')->sum('amount');

        return [
            'datasets' => [
                [
                    'data' => [$tickets, $products],
                    'backgroundColor' => ['#f59e0b', '#10b981'],
                    'borderColor' => ['#f59e0b', '#10b981'],
                ],
            ],
            'labels' => ['Tickets', 'Products'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
