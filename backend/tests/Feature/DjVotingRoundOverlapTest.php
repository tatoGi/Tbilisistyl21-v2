<?php

namespace Tests\Feature;

use App\Models\DjVotingRound;
use App\Rules\NoOverlappingRound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DjVotingRoundOverlapTest extends TestCase
{
    use RefreshDatabase;

    private function existingRound(): DjVotingRound
    {
        return DjVotingRound::create([
            'title' => 'Existing',
            'starts_at' => now()->startOfHour(),
            'ends_at' => now()->startOfHour()->addHours(24),
        ]);
    }

    private function fails(?string $ignoreId, string $startsAt, int $hours): bool
    {
        $validator = Validator::make(
            ['starts_at' => $startsAt],
            ['starts_at' => [new NoOverlappingRound($ignoreId, $startsAt, $hours)]],
        );

        return $validator->fails();
    }

    public function test_overlapping_window_is_rejected(): void
    {
        $this->existingRound();

        $this->assertTrue($this->fails(null, now()->startOfHour()->addHours(2)->toDateTimeString(), 24));
    }

    public function test_window_starting_after_the_existing_one_ends_is_allowed(): void
    {
        $this->existingRound();

        $this->assertFalse($this->fails(null, now()->startOfHour()->addHours(25)->toDateTimeString(), 24));
    }

    public function test_a_round_does_not_conflict_with_itself(): void
    {
        $round = $this->existingRound();

        $this->assertFalse($this->fails($round->id, $round->starts_at->toDateTimeString(), 24));
    }
}
