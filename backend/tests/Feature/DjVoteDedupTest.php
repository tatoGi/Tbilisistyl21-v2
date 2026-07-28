<?php

namespace Tests\Feature;

use App\Models\Dj;
use App\Models\DjVote;
use App\Models\DjVotingRound;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjVoteDedupTest extends TestCase
{
    use RefreshDatabase;

    private function round(): DjVotingRound
    {
        return DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }

    public function test_same_token_cannot_vote_twice_in_one_round(): void
    {
        $round = $this->round();
        $a = Dj::create(['name' => 'A', 'status' => 'published']);
        $b = Dj::create(['name' => 'B', 'status' => 'published']);

        DjVote::create(['round_id' => $round->id, 'dj_id' => $a->id, 'voter_token' => 'tok-1']);

        $this->expectException(QueryException::class);
        DjVote::create(['round_id' => $round->id, 'dj_id' => $b->id, 'voter_token' => 'tok-1']);
    }

    public function test_same_token_may_vote_in_a_different_round(): void
    {
        $first = $this->round();
        $second = DjVotingRound::create([
            'title' => 'Next',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);

        DjVote::create(['round_id' => $first->id, 'dj_id' => $dj->id, 'voter_token' => 'tok-1']);
        DjVote::create(['round_id' => $second->id, 'dj_id' => $dj->id, 'voter_token' => 'tok-1']);

        $this->assertSame(2, DjVote::where('voter_token', 'tok-1')->count());
    }
}
