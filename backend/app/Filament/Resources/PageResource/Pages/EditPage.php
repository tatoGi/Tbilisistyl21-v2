<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditPage extends EditRecord
{
    use Translatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /** Persist image paths as `/storage/...` so the frontend API can serve them. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['content_blocks']) && is_array($data['content_blocks'])) {
            $data['content_blocks'] = PageResource::blocksForForm($data['content_blocks']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['content_blocks']) && is_array($data['content_blocks'])) {
            $data['content_blocks'] = PageResource::normalizeBlockPaths($data['content_blocks']);
        }

        return $data;
    }
}
