<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
