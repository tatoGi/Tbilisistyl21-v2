<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use App\Models\Page;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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

        $record = $this->getRecord();
        $existing = $record instanceof Page ? $record : null;

        return PageResource::normalizeSettings($data, $existing);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['content_blocks']) && is_array($data['content_blocks'])) {
            $data['content_blocks'] = PageResource::normalizeBlockPaths($data['content_blocks']);
        }

        $record = $this->getRecord();
        $existing = $record instanceof Page ? $record : null;

        // Merge Livewire state — Translatable saves can omit sidebar toggles from $data.
        return PageResource::normalizeSettings($data, $existing, $this->data ?? []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        if (! $record instanceof Page) {
            return $record;
        }

        $settings = PageResource::normalizeSettings(
            $data,
            $record,
            array_merge($this->data ?? [], $data),
        );

        $record->fill(Arr::only($settings, PageResource::persistedSettingsKeys()));
        $record->saveQuietly();

        return $record;
    }
}
