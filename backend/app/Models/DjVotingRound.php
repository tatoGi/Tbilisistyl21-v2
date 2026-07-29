<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DjVotingRound extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'starts_at', 'ends_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function djs(): BelongsToMany
    {
        // `order` is a reserved word, so it must be quoted — and the quoting
        // has to come from the connection's grammar, not be hardcoded:
        // Postgres (what we run on) rejects the MySQL backtick outright.
        $grammar = $this->getConnection()->getQueryGrammar();

        return $this->belongsToMany(Dj::class, 'dj_voting_round_dj', 'round_id', 'dj_id')
            ->using(DjVotingRoundDj::class)
            ->withPivot('order')
            ->orderByRaw(sprintf(
                'COALESCE(%s, %s)',
                $grammar->wrap('dj_voting_round_dj.order'),
                $grammar->wrap('djs.order'),
            ));
    }

    public function votes(): HasMany
    {
        return $this->hasMany(DjVote::class, 'round_id');
    }

    /** Duration units the admin can pick, longest-lived first. */
    public const DURATION_UNITS = ['months', 'days', 'hours'];

    /**
     * The single place a duration becomes an `ends_at`, shared by the form
     * pages and the overlap rule so a round can never be validated against a
     * different window than the one that gets stored.
     */
    public static function resolveEndsAt(Carbon|string $startsAt, int $value, string $unit): Carbon
    {
        $start = $startsAt instanceof Carbon ? $startsAt->copy() : Carbon::parse($startsAt);

        return match ($unit) {
            // NoOverflow: plain addMonths turns 31 Jan + 1 month into 3 March,
            // which is not what an admin picking "1 month" means.
            'months' => $start->addMonthsNoOverflow($value),
            'days' => $start->addDays($value),
            default => $start->addHours($value),
        };
    }

    /**
     * The inverse of resolveEndsAt, for filling the edit form: report the
     * largest unit that reproduces the stored window exactly, so "5 months"
     * comes back as 5 months rather than 3672 hours. Anything that is not a
     * whole number of larger units stays in hours.
     */
    public static function describeDuration(Carbon $startsAt, Carbon $endsAt): array
    {
        foreach (static::DURATION_UNITS as $unit) {
            $value = match ($unit) {
                'months' => $startsAt->diffInMonths($endsAt),
                'days' => $startsAt->diffInDays($endsAt),
                default => $startsAt->diffInHours($endsAt),
            };

            $value = (int) $value;

            if ($value >= 1 && static::resolveEndsAt($startsAt, $value, $unit)->equalTo($endsAt)) {
                return ['value' => $value, 'unit' => $unit];
            }
        }

        return ['value' => max(1, (int) $startsAt->diffInHours($endsAt)), 'unit' => 'hours'];
    }

    /** Derived from the clock so it can never drift out of sync. */
    public function state(): string
    {
        $now = now();

        if ($now->lt($this->starts_at)) {
            return 'scheduled';
        }

        return $now->lt($this->ends_at) ? 'open' : 'closed';
    }

    public function isOpen(): bool
    {
        return $this->state() === 'open';
    }

    /** The single round that is open right now, if any. */
    public static function current(): ?self
    {
        return static::query()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }
}
