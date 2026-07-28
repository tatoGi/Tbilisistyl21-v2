<?php

namespace App\Filament\Resources\DjResource\Pages;

use App\Filament\Resources\DjResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDj extends EditRecord
{
    protected static string $resource = DjResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['photo_upload'] = DjResource::photoForForm($this->record->photo);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DjResource::mergeUploadedMedia($data, 'photo_upload', 'photo_id');
    }
}
