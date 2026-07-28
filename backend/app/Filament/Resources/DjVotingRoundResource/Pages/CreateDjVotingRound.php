<?php

namespace App\Filament\Resources\DjVotingRoundResource\Pages;

use App\Filament\Resources\DjVotingRoundResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateDjVotingRound extends CreateRecord
{
    protected static string $resource = DjVotingRoundResource::class;

    /** `ends_at` is always derived from start + duration, never edited directly. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $hours = (int) ($data['duration_hours'] ?? 24);
        unset($data['duration_hours']);

        $data['ends_at'] = Carbon::parse($data['starts_at'])->addHours($hours);

        return $data;
    }
}
