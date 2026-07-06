<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['name'] = $this->record->getTranslations('name');
        $data['description'] = $this->record->getTranslations('description');
        $data['logo_upload'] = PartnerResource::logoForForm($this->record->logo);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PartnerResource::mergeUploadedLogo($data);
    }
}
