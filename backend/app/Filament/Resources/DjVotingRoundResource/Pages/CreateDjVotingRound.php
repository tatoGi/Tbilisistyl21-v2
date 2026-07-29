<?php

namespace App\Filament\Resources\DjVotingRoundResource\Pages;

use App\Filament\Resources\DjVotingRoundResource;
use App\Models\DjVotingRound;
use Filament\Resources\Pages\CreateRecord;

class CreateDjVotingRound extends CreateRecord
{
    protected static string $resource = DjVotingRoundResource::class;

    /** `ends_at` is always derived from start + duration, never edited directly. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $value = (int) ($data['duration_value'] ?? 24);
        $unit = (string) ($data['duration_unit'] ?? 'hours');
        unset($data['duration_value'], $data['duration_unit']);

        $data['ends_at'] = DjVotingRound::resolveEndsAt($data['starts_at'], $value, $unit);

        return $data;
    }
}
