<?php

namespace Tests\Feature;

use App\Filament\Resources\DjVotingRoundResource\Pages\ListDjVotingRounds;
use App\Models\DjVotingRound;
use App\Models\User;
use App\Rules\NoOverlappingRound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
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

    /**
     * The predicate is strict `<`/`>`, so the interval is [starts_at, ends_at).
     * A new window that starts exactly when the existing one ends must be allowed.
     */
    public function test_exact_abutting_start_is_allowed(): void
    {
        $round = $this->existingRound();

        $this->assertFalse($this->fails(null, $round->ends_at->toDateTimeString(), 24));
    }

    /**
     * Symmetric boundary case: a new window that ends exactly when the
     * existing one starts must also be allowed.
     */
    public function test_exact_abutting_end_is_allowed(): void
    {
        $round = $this->existingRound();

        $this->assertFalse($this->fails(
            null,
            $round->starts_at->copy()->subHours(24)->toDateTimeString(),
            24,
        ));
    }

    /**
     * Proves the boundary is exact rather than merely loose: shifting the
     * abutting start back by even one minute must be rejected.
     */
    public function test_one_minute_overlap_is_rejected(): void
    {
        $round = $this->existingRound();

        $this->assertTrue($this->fails(
            null,
            $round->ends_at->copy()->subMinute()->toDateTimeString(),
            1,
        ));
    }

    /**
     * Proves whereKeyNot excludes only the round being edited, not every
     * other round: editing round B into a collision with round A (a
     * different, non-overlapping round) must still fail.
     */
    public function test_editing_a_round_into_conflict_with_a_different_round_is_rejected(): void
    {
        $roundA = $this->existingRound();

        $roundB = DjVotingRound::create([
            'title' => 'Other',
            'starts_at' => $roundA->ends_at->copy()->addHour(),
            'ends_at' => $roundA->ends_at->copy()->addHours(25),
        ]);

        $this->assertTrue($this->fails(
            $roundB->id,
            $roundA->starts_at->toDateTimeString(),
            24,
        ));
    }

    /**
     * The "Start now" table action shares NoOverlappingRound::conflictExists
     * with the form rule. This exercises that shared predicate through the
     * actual Filament action rather than duplicating the logic in the test.
     */
    public function test_start_now_action_rejects_a_conflict_with_another_round(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->existingRound(); // now .. now+24h, currently open

        $originalStartsAt = now()->addHours(2);
        $originalEndsAt = now()->addHours(26);

        $scheduledRound = DjVotingRound::create([
            'title' => 'Scheduled',
            'starts_at' => $originalStartsAt,
            'ends_at' => $originalEndsAt,
        ]);

        Livewire::test(ListDjVotingRounds::class)
            ->callTableAction('startNow', $scheduledRound)
            ->assertNotified('Another round is already running');

        $scheduledRound->refresh();

        // The action must leave the round untouched when it detects a conflict.
        $this->assertSame($originalStartsAt->toDateTimeString(), $scheduledRound->starts_at->toDateTimeString());
        $this->assertSame($originalEndsAt->toDateTimeString(), $scheduledRound->ends_at->toDateTimeString());
    }
}
