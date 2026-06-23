<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InventoryAlerts extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Alerts';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->where('status', 'active')
                    ->where('quantity', '<', 5)
                    ->orderBy('quantity')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('quantity')
                    ->badge()
                    ->color(fn (int $state): string => $state <= 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('event_date')->date(),
            ]);
    }
}
