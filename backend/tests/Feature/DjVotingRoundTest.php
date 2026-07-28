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
}
