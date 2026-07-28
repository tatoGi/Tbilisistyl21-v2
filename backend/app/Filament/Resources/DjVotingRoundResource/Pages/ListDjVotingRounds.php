<?php

namespace App\Filament\Resources\DjVotingRoundResource\Pages;

use App\Filament\Resources\DjVotingRoundResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDjVotingRounds extends ListRecords
{
    protected static string $resource = DjVotingRoundResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
