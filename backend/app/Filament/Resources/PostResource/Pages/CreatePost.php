<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['content_blocks']) && is_array($data['content_blocks'])) {
            $data['content_blocks'] = PostResource::normalizeBlockPaths($data['content_blocks']);
        }

        return $data;
    }
}
