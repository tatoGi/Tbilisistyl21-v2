<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\AdminOnlyWidget;
use App\Models\Dj;
use App\Models\DjVotingRound;
use App\Services\DjVoteService;
use Filament\Widgets\Widget;

class DjVoteResults extends Widget
{
    use AdminOnlyWidget;

    protected static string $view = 'filament.widgets.dj-vote-results';

    protected int|string|array $columnSpan = 'full';

    public ?DjVotingRound $record = null;

    /** Vote totals for this round, highest first, with DJ names resolved. */
    public function rows(): array
    {
        if (!$this->record) {
            return [];
        }

        $results = app(DjVoteService::class)->results($this->record);

        $names = Dj::whereIn('id', array_column($results, 'djId'))
            ->pluck('name', 'id');

        return array_map(fn (array $row) => [
            'name' => $names[$row['djId']] ?? 'Deleted DJ',
            'votes' => $row['votes'],
            'percent' => $row['percent'],
        ], $results);
    }

    public function totalVotes(): int
    {
        return array_sum(array_column($this->rows(), 'votes'));
    }
}
