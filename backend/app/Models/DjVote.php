<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DjVote extends Model
{
    use HasUuids;

    protected $fillable = ['round_id', 'dj_id', 'voter_token', 'ip_hash'];

    public function round(): BelongsTo
    {
        return $this->belongsTo(DjVotingRound::class, 'round_id');
    }

    public function dj(): BelongsTo
    {
        return $this->belongsTo(Dj::class, 'dj_id');
    }

    /** Hashed so the raw address is never stored. */
    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip . config('app.key')) : null;
    }
}
