<?php

namespace Tests\Feature;

use App\Filament\Widgets\DjVoteResults;
use App\Models\Dj;
use App\Models\DjVotingRound;
use App\Services\DjVoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjVoteResultsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_rows_are_ordered_by_votes_descending(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $a = Dj::create(['name' => 'Quiet', 'status' => 'published']);
        $b = Dj::create(['name' => 'Popular', 'status' => 'published']);
        $round->djs()->attach([$a->id, $b->id]);

        $service = app(DjVoteService::class);
        $service->castVote($round, $b->id, 'tok-1', null);
        $service->castVote($round, $b->id, 'tok-2', null);
        $service->castVote($round, $a->id, 'tok-3', null);

        $widget = new DjVoteResults();
        $widget->record = $round;

        $rows = $widget->rows();

        $this->assertSame('Popular', $rows[0]['name']);
        $this->assertSame(2, $rows[0]['votes']);
        $this->assertSame(66.7, $rows[0]['percent']);
        $this->assertSame('Quiet', $rows[1]['name']);
    }

    public function test_a_round_with_no_votes_renders_zeroes(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Empty',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $round->djs()->attach(Dj::create(['name' => 'Nobody', 'status' => 'published'])->id);

        $widget = new DjVoteResults();
        $widget->record = $round;

        $rows = $widget->rows();

        $this->assertSame(0, $rows[0]['votes']);
        $this->assertSame(0.0, $rows[0]['percent']);
    }

    public function test_total_votes_sums_every_row(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $a = Dj::create(['name' => 'A', 'status' => 'published']);
        $b = Dj::create(['name' => 'B', 'status' => 'published']);
        $round->djs()->attach([$a->id, $b->id]);

        $service = app(DjVoteService::class);
        $service->castVote($round, $a->id, 'tok-1', null);
        $service->castVote($round, $b->id, 'tok-2', null);

        $widget = new DjVoteResults();
        $widget->record = $round;

        $this->assertSame(2, $widget->totalVotes());
    }

    /**
     * A DJ unpublished mid-round keeps their votes in the admin statistics —
     * the ballot hides them, the tally must not.
     */
    public function test_unpublished_djs_still_appear_with_their_votes(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $dj = Dj::create(['name' => 'Withdrawn', 'status' => 'published']);
        $round->djs()->attach($dj->id);

        app(DjVoteService::class)->castVote($round, $dj->id, 'tok-1', null);
        $dj->update(['status' => 'draft']);

        $rows = tap(new DjVoteResults(), fn ($w) => $w->record = $round)->rows();

        $this->assertSame('Withdrawn', $rows[0]['name']);
        $this->assertSame(1, $rows[0]['votes']);
    }

    public function test_without_a_record_there_are_no_rows(): void
    {
        $this->assertSame([], (new DjVoteResults())->rows());
    }
}
