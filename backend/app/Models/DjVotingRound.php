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
        return $this->belongsToMany(Dj::class, 'dj_voting_round_dj', 'round_id', 'dj_id')
            ->using(DjVotingRoundDj::class)
            ->withPivot('order')
            ->orderByRaw('COALESCE(dj_voting_round_dj.`order`, djs.`order`)');
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
