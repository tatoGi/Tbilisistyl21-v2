<?php

namespace App\Filament\Resources\DjResource\Pages;

use App\Filament\Resources\DjResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDjs extends ListRecords
{
    protected static string $resource = DjResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
