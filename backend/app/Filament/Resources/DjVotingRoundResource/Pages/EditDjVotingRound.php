<?php

namespace App\Filament\Resources\DjVotingRoundResource\Pages;

use App\Filament\Resources\DjVotingRoundResource;
use App\Filament\Widgets\DjVoteResults;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditDjVotingRound extends EditRecord
{
    protected static string $resource = DjVotingRoundResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getFooterWidgets(): array
    {
        return [DjVoteResults::class];
    }

    /** Show the stored window back as a duration in hours. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['duration_hours'] = Carbon::parse($data['starts_at'])
            ->diffInHours(Carbon::parse($data['ends_at']));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $hours = (int) ($data['duration_hours'] ?? 24);
        unset($data['duration_hours']);

        $data['ends_at'] = Carbon::parse($data['starts_at'])->addHours($hours);

        return $data;
    }
}
