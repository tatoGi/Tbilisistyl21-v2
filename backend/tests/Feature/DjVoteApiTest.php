<?php

namespace Tests\Feature;

use App\Models\Dj;
use App\Models\DjVote;
use App\Models\DjVotingRound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjVoteApiTest extends TestCase
{
    use RefreshDatabase;

    private function openRound(): DjVotingRound
    {
        return DjVotingRound::create([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }

    private function headers(string $token = 'tok-1'): array
    {
        return ['X-Vote-Token' => $token];
    }

    public function test_no_open_round_returns_null_round(): void
    {
        $this->getJson('/api/dj-vote', $this->headers())
            ->assertOk()
            ->assertJson(['round' => null, 'hasVoted' => false]);
    }

    public function test_scheduled_round_is_not_yet_visible(): void
    {
        DjVotingRound::create([
            'title' => 'Later',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(25),
        ]);

        $this->getJson('/api/dj-vote', $this->headers())
            ->assertOk()
            ->assertJson(['round' => null]);
    }

    public function test_ballot_lists_only_published_djs(): void
    {
        $round = $this->openRound();
        $live = Dj::create(['name' => 'Live', 'status' => 'published']);
        $draft = Dj::create(['name' => 'Draft', 'status' => 'draft']);
        $round->djs()->attach([$live->id, $draft->id]);

        $response = $this->getJson('/api/dj-vote', $this->headers())->assertOk();

        $this->assertSame(['Live'], array_column($response->json('djs'), 'name'));
    }

    public function test_bio_is_returned_with_every_locale(): void
    {
        $round = $this->openRound();
        $dj = Dj::create([
            'name' => 'Polyglot',
            'bio' => ['ka' => 'ქართული', 'en' => 'English'],
            'status' => 'published',
        ]);
        $round->djs()->attach($dj->id);

        $response = $this->getJson('/api/dj-vote', $this->headers())->assertOk();

        // The frontend localizes with its own t(); a single collapsed string
        // would silently break every non-default language.
        $this->assertSame(
            ['ka' => 'ქართული', 'en' => 'English'],
            $response->json('djs.0.bio'),
        );
    }

    public function test_results_are_hidden_before_voting_and_shown_after(): void
    {
        $round = $this->openRound();
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);
        $round->djs()->attach($dj->id);

        $before = $this->getJson('/api/dj-vote', $this->headers())->assertOk();
        $this->assertFalse($before->json('hasVoted'));
        $this->assertNull($before->json('results'));

        $this->postJson('/api/dj-vote', ['djId' => $dj->id], $this->headers())
            ->assertOk()
            ->assertJson(['hasVoted' => true, 'votedDjId' => $dj->id]);

        $after = $this->getJson('/api/dj-vote', $this->headers())->assertOk();
        $this->assertTrue($after->json('hasVoted'));
        $this->assertSame(1, $after->json('results.0.votes'));
    }

    public function test_changing_a_vote_does_not_add_a_second_one(): void
    {
        $round = $this->openRound();
        $a = Dj::create(['name' => 'A', 'status' => 'published']);
        $b = Dj::create(['name' => 'B', 'status' => 'published']);
        $round->djs()->attach([$a->id, $b->id]);

        $this->postJson('/api/dj-vote', ['djId' => $a->id], $this->headers())->assertOk();
        $this->postJson('/api/dj-vote', ['djId' => $b->id], $this->headers())
            ->assertOk()
            ->assertJson(['votedDjId' => $b->id]);

        $this->assertSame(1, DjVote::where('round_id', $round->id)->count());
    }

    public function test_two_tokens_are_two_votes(): void
    {
        $round = $this->openRound();
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);
        $round->djs()->attach($dj->id);

        $this->postJson('/api/dj-vote', ['djId' => $dj->id], $this->headers('tok-1'))->assertOk();
        $this->postJson('/api/dj-vote', ['djId' => $dj->id], $this->headers('tok-2'))->assertOk();

        $this->assertSame(2, DjVote::where('round_id', $round->id)->count());
    }

    public function test_voting_without_an_open_round_is_rejected(): void
    {
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);

        $this->postJson('/api/dj-vote', ['djId' => $dj->id], $this->headers())
            ->assertStatus(409);
    }

    public function test_voting_for_a_dj_outside_the_round_is_rejected(): void
    {
        $round = $this->openRound();
        $onBallot = Dj::create(['name' => 'A', 'status' => 'published']);
        $elsewhere = Dj::create(['name' => 'B', 'status' => 'published']);
        $round->djs()->attach($onBallot->id);

        $this->postJson('/api/dj-vote', ['djId' => $elsewhere->id], $this->headers())
            ->assertStatus(422);
    }

    public function test_a_new_round_counts_from_zero_without_losing_history(): void
    {
        $first = $this->openRound();
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);
        $first->djs()->attach($dj->id);
        $this->postJson('/api/dj-vote', ['djId' => $dj->id], $this->headers())->assertOk();

        // Close the first round and open a fresh one.
        $first->update(['ends_at' => now()->subMinute()]);
        $second = $this->openRound();
        $second->djs()->attach($dj->id);

        $response = $this->getJson('/api/dj-vote', $this->headers())->assertOk();

        $this->assertFalse($response->json('hasVoted'));
        $this->assertSame(1, DjVote::where('round_id', $first->id)->count());
        $this->assertSame(0, DjVote::where('round_id', $second->id)->count());
    }

    public function test_a_request_without_a_token_is_rejected(): void
    {
        $round = $this->openRound();
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);
        $round->djs()->attach($dj->id);

        $this->postJson('/api/dj-vote', ['djId' => $dj->id])->assertStatus(422);
    }

    public function test_get_without_a_token_still_returns_the_ballot(): void
    {
        $round = $this->openRound();
        $dj = Dj::create(['name' => 'A', 'status' => 'published']);
        $round->djs()->attach($dj->id);

        $response = $this->getJson('/api/dj-vote')
            ->assertOk()
            ->assertJson(['hasVoted' => false, 'votedDjId' => null, 'results' => null]);

        $this->assertSame(['A'], array_column($response->json('djs'), 'name'));
    }

    public function test_get_without_a_token_and_without_an_open_round_returns_null_round(): void
    {
        $this->getJson('/api/dj-vote')
            ->assertOk()
            ->assertJson(['round' => null]);
    }
}
