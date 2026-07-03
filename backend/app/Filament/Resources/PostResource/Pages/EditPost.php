<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Expand the spatie-translatable attributes into their full
     * {ka,en,ru,ua} arrays so the per-locale form fields load every language.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['title'] = $this->record->getTranslations('title');

        if (isset($data['content_blocks']) && is_array($data['content_blocks'])) {
            $data['content_blocks'] = PostResource::blocksForForm($data['content_blocks']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['content_blocks']) && is_array($data['content_blocks'])) {
            $data['content_blocks'] = PostResource::normalizeBlockPaths($data['content_blocks']);
        }

        return $data;
    }
}
