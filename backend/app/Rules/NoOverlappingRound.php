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
        private int $durationHours,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->startsAt || $this->durationHours < 1) {
            return;
        }

        $start = Carbon::parse($this->startsAt);
        $end = $start->copy()->addHours($this->durationHours);

        $conflict = DjVotingRound::query()
            ->when($this->ignoreId, fn ($q) => $q->whereKeyNot($this->ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();

        if ($conflict) {
            $fail('This window overlaps another voting round. Only one round may run at a time.');
        }
    }
}
