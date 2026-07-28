<?php

namespace App\Filament\Resources\DjResource\Pages;

use App\Filament\Resources\DjResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDj extends CreateRecord
{
    protected static string $resource = DjResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DjResource::mergeUploadedMedia($data, 'photo_upload', 'photo_id');
    }
}
