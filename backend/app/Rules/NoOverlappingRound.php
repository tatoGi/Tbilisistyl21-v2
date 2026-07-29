<?php

namespace App\Rules;

use App\Models\DjVotingRound;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Keeps the "at most one open round" invariant that lets the public endpoint
 * resolve the current round without a parameter.
 */
class NoOverlappingRound implements ValidationRule
{
    public function __construct(
        private ?string $ignoreId,
        private ?string $startsAt,
        private int $durationValue,
        private string $durationUnit = 'hours',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->startsAt || $this->durationValue < 1) {
            return;
        }

        $start = Carbon::parse($this->startsAt);
        // Same helper the form pages use, so the window that gets validated is
        // exactly the window that gets stored.
        $end = DjVotingRound::resolveEndsAt($start, $this->durationValue, $this->durationUnit);

        if (static::conflictExists($this->ignoreId, $start, $end)) {
            $fail('This window overlaps another voting round. Only one round may run at a time.');
        }
    }

    /**
     * The single source of truth for the overlap predicate, shared by this
     * rule (form-time validation) and any action that mutates a round's
     * window at runtime (e.g. the "Start now" table action), so the
     * invariant can't drift between the two call sites.
     */
    public static function conflictExists(?string $ignoreId, Carbon $start, Carbon $end): bool
    {
        return DjVotingRound::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }
}
