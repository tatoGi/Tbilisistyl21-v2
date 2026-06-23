<?php

namespace App\Filament\Resources\SoldTicketResource\Pages;

use App\Filament\Resources\SoldTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoldTicket extends EditRecord
{
    protected static string $resource = SoldTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
