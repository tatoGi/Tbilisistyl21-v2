<?php

namespace App\Filament\Resources\DjVotingRoundResource\Pages;

use App\Filament\Resources\DjVotingRoundResource;
use App\Filament\Widgets\DjVoteResults;
use App\Models\DjVotingRound;
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

    /** Show the stored window back in the largest unit that reproduces it. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $duration = DjVotingRound::describeDuration(
            Carbon::parse($data['starts_at']),
            Carbon::parse($data['ends_at']),
        );

        $data['duration_value'] = $duration['value'];
        $data['duration_unit'] = $duration['unit'];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $value = (int) ($data['duration_value'] ?? 24);
        $unit = (string) ($data['duration_unit'] ?? 'hours');
        unset($data['duration_value'], $data['duration_unit']);

        $data['ends_at'] = DjVotingRound::resolveEndsAt($data['starts_at'], $value, $unit);

        return $data;
    }
}
