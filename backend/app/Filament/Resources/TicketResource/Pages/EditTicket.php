<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['title'] = $this->record->getTranslations('title');
        $data['description'] = $this->record->getTranslations('description');

        if (!empty($data['image'])) {
            $data['image'] = TicketResource::fileUploadState($data['image']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('image', $data)) {
            $data['image'] = TicketResource::publicUrl(TicketResource::extractFilePath($data['image']));
        }

        return $data;
    }
}
