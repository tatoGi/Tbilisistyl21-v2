<?php

namespace Tests\Feature;

use App\Models\DjVotingRound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DjVotingRoundDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hours_are_added_to_the_start(): void
    {
        $end = DjVotingRound::resolveEndsAt('2026-08-01 10:00:00', 6, 'hours');

        $this->assertSame('2026-08-01 16:00:00', $end->toDateTimeString());
    }

    public function test_days_are_added_to_the_start(): void
    {
        $end = DjVotingRound::resolveEndsAt('2026-08-01 10:00:00', 3, 'days');

        $this->assertSame('2026-08-04 10:00:00', $end->toDateTimeString());
    }

    /** The point of this feature: run a round for months, e.g. until December. */
    public function test_months_are_added_to_the_start(): void
    {
        $end = DjVotingRound::resolveEndsAt('2026-07-29 10:00:00', 5, 'months');

        $this->assertSame('2026-12-29 10:00:00', $end->toDateTimeString());
    }

    /** Carbon clamps a month-end start rather than spilling into the next month. */
    public function test_month_end_start_does_not_spill_into_the_next_month(): void
    {
        $end = DjVotingRound::resolveEndsAt('2026-01-31 10:00:00', 1, 'months');

        $this->assertSame('2026-02-28 10:00:00', $end->toDateTimeString());
    }

    public function test_an_unknown_unit_falls_back_to_hours(): void
    {
        $end = DjVotingRound::resolveEndsAt('2026-08-01 10:00:00', 2, 'fortnights');

        $this->assertSame('2026-08-01 12:00:00', $end->toDateTimeString());
    }

    /**
     * The edit form has to show a stored window back as a value + unit, so the
     * description must pick the largest unit that reproduces the exact window.
     */
    public function test_describe_duration_prefers_months_then_days_then_hours(): void
    {
        $this->assertSame(
            ['value' => 5, 'unit' => 'months'],
            DjVotingRound::describeDuration(
                Carbon::parse('2026-07-29 10:00:00'),
                Carbon::parse('2026-12-29 10:00:00'),
            ),
        );

        $this->assertSame(
            ['value' => 3, 'unit' => 'days'],
            DjVotingRound::describeDuration(
                Carbon::parse('2026-08-01 10:00:00'),
                Carbon::parse('2026-08-04 10:00:00'),
            ),
        );

        $this->assertSame(
            ['value' => 6, 'unit' => 'hours'],
            DjVotingRound::describeDuration(
                Carbon::parse('2026-08-01 10:00:00'),
                Carbon::parse('2026-08-01 16:00:00'),
            ),
        );
    }

    /** A window that is not a whole number of larger units stays in hours. */
    public function test_describe_duration_falls_back_to_hours_for_odd_windows(): void
    {
        $this->assertSame(
            ['value' => 30, 'unit' => 'hours'],
            DjVotingRound::describeDuration(
                Carbon::parse('2026-08-01 10:00:00'),
                Carbon::parse('2026-08-02 16:00:00'),
            ),
        );
    }

    /** Round-trip: whatever the form stores must come back out unchanged. */
    public function test_describe_duration_round_trips_resolve_ends_at(): void
    {
        foreach ([[6, 'hours'], [3, 'days'], [5, 'months'], [1, 'months']] as [$value, $unit]) {
            $start = Carbon::parse('2026-07-29 10:00:00');
            $end = DjVotingRound::resolveEndsAt($start, $value, $unit);

            $this->assertSame(
                ['value' => $value, 'unit' => $unit],
                DjVotingRound::describeDuration($start, $end),
                "round-trip failed for {$value} {$unit}",
            );
        }
    }

    /** A round spanning months must still be recognised as open right now. */
    public function test_a_multi_month_round_is_open(): void
    {
        $round = DjVotingRound::create([
            'title' => 'Until December',
            'starts_at' => now()->subDay(),
            'ends_at' => DjVotingRound::resolveEndsAt(now()->subDay(), 5, 'months'),
        ]);

        $this->assertSame('open', $round->state());
        $this->assertSame($round->id, DjVotingRound::current()?->id);
    }
}
