<?php

namespace Tests\Feature;

use App\Models\Dj;
use App\Models\DjVotingRound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjVotingRoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_is_derived_from_the_clock(): void
    {
        $scheduled = DjVotingRound::create([
            'title' => 'Later',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(25),
        ]);
        $open = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $closed = DjVotingRound::create([
            'title' => 'Done',
            'starts_at' => now()->subHours(25),
            'ends_at' => now()->subHour(),
        ]);

        $this->assertSame('scheduled', $scheduled->state());
        $this->assertSame('open', $open->state());
        $this->assertSame('closed', $closed->state());
    }

    public function test_current_returns_only_the_open_round(): void
    {
        DjVotingRound::create([
            'title' => 'Done',
            'starts_at' => now()->subHours(25),
            'ends_at' => now()->subHour(),
        ]);

        $this->assertNull(DjVotingRound::current());

        $open = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $this->assertSame($open->id, DjVotingRound::current()?->id);
    }

    public function test_round_has_many_djs(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $dj = Dj::create(['name' => 'One', 'status' => 'published']);

        $round->djs()->attach($dj->id, ['order' => 1]);

        $this->assertSame(['One'], $round->refresh()->djs->pluck('name')->all());
    }

    /**
     * The ballot ordering is raw SQL, and `order` is a reserved word that has
     * to be quoted. The test suite runs on SQLite, which tolerates MySQL
     * backticks; the app runs on Postgres, which rejects them outright. So
     * assert the identifier quoting comes from the connection's own grammar
     * rather than being hardcoded for one driver.
     */
    public function test_ballot_ordering_is_quoted_by_the_connection_grammar(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $sql = $round->djs()->toSql();
        $grammar = $round->getConnection()->getQueryGrammar();

        $this->assertStringNotContainsString('`', $sql);
        $this->assertStringContainsString(
            sprintf(
                'coalesce(%s, %s)',
                $grammar->wrap('dj_voting_round_dj.order'),
                $grammar->wrap('djs.order'),
            ),
            strtolower($sql),
        );
    }

    /** Per-round order wins; DJs without an override fall back to their own. */
    public function test_ballot_order_prefers_the_pivot_override(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        $first = Dj::create(['name' => 'First', 'order' => 9, 'status' => 'published']);
        $second = Dj::create(['name' => 'Second', 'order' => 2, 'status' => 'published']);

        $round->djs()->attach($first->id, ['order' => 1]);
        $round->djs()->attach($second->id);

        $this->assertSame(['First', 'Second'], $round->djs()->pluck('name')->all());
    }
}
