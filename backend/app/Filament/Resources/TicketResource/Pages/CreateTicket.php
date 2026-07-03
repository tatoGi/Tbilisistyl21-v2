<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('image', $data)) {
            $data['image'] = TicketResource::publicUrl(TicketResource::extractFilePath($data['image']));
        }

        return $data;
    }
}
