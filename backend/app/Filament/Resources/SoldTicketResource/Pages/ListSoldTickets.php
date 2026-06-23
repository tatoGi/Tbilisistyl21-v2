<?php

namespace App\Filament\Resources\SoldTicketResource\Pages;

use App\Filament\Resources\SoldTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoldTickets extends ListRecords
{
    protected static string $resource = SoldTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
