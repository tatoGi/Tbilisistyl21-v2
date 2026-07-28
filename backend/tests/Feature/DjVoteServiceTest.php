<?php

namespace Tests\Feature;

use App\Models\Dj;
use App\Models\DjVote;
use App\Models\DjVotingRound;
use App\Services\DjVoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjVoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private DjVotingRound $round;
    private Dj $a;
    private Dj $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $this->a = Dj::create(['name' => 'A', 'status' => 'published']);
        $this->b = Dj::create(['name' => 'B', 'status' => 'published']);
        $this->round->djs()->attach([$this->a->id, $this->b->id]);
    }

    public function test_empty_round_reports_zero_percent_without_dividing_by_zero(): void
    {
        $results = app(DjVoteService::class)->results($this->round);

        $this->assertCount(2, $results);
        $this->assertSame(0, $results[0]['votes']);
        $this->assertSame(0.0, $results[0]['percent']);
    }

    public function test_percentages_are_relative_to_the_round_total(): void
    {
        $service = app(DjVoteService::class);
        $service->castVote($this->round, $this->a->id, 'tok-1', '10.0.0.1');
        $service->castVote($this->round, $this->a->id, 'tok-2', '10.0.0.2');
        $service->castVote($this->round, $this->b->id, 'tok-3', '10.0.0.3');

        $results = collect($service->results($this->round))->keyBy('djId');

        $this->assertSame(2, $results[$this->a->id]['votes']);
        $this->assertSame(66.7, $results[$this->a->id]['percent']);
        $this->assertSame(1, $results[$this->b->id]['votes']);
        $this->assertSame(33.3, $results[$this->b->id]['percent']);
    }

    public function test_changing_a_vote_updates_instead_of_inserting(): void
    {
        $service = app(DjVoteService::class);
        $service->castVote($this->round, $this->a->id, 'tok-1', '10.0.0.1');
        $service->castVote($this->round, $this->b->id, 'tok-1', '10.0.0.1');

        $this->assertSame(1, DjVote::where('round_id', $this->round->id)->count());
        $this->assertSame(
            $this->b->id,
            DjVote::where('voter_token', 'tok-1')->first()->dj_id,
        );
    }

    public function test_ip_is_stored_hashed_not_raw(): void
    {
        app(DjVoteService::class)->castVote($this->round, $this->a->id, 'tok-1', '10.0.0.1');

        $stored = DjVote::where('voter_token', 'tok-1')->first()->ip_hash;

        $this->assertNotSame('10.0.0.1', $stored);
        $this->assertSame(64, strlen($stored));
    }

    public function test_results_include_djs_removed_from_the_ballot(): void
    {
        $service = app(DjVoteService::class);
        $service->castVote($this->round, $this->a->id, 'tok-1', null);

        $this->a->update(['status' => 'draft']);

        $results = collect($service->results($this->round))->keyBy('djId');

        $this->assertSame(1, $results[$this->a->id]['votes']);
    }
}
