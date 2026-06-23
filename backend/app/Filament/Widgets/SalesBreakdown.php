<?php

namespace App\Filament\Widgets;

use App\Models\ProductOrder;
use App\Models\SoldTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesBreakdown extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $ticketRevenue = SoldTicket::where('status', 'paid')->sum('amount');
        $ticketCount = SoldTicket::where('status', 'paid')->count();
        $productRevenue = ProductOrder::where('status', 'paid')->sum('amount');
        $productCount = ProductOrder::where('status', 'paid')->count();

        return [
            Stat::make('Ticket Revenue', number_format($ticketRevenue) . ' GEL')
                ->description("{$ticketCount} tickets sold")
                ->color('success'),
            Stat::make('Product Revenue', number_format($productRevenue) . ' GEL')
                ->description("{$productCount} orders")
                ->color('info'),
            Stat::make('Total Revenue', number_format($ticketRevenue + $productRevenue) . ' GEL')
                ->description('Combined sales')
                ->color('warning'),
        ];
    }
}
