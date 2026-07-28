<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DjVotingRoundDj extends Pivot
{
    use HasUuids;

    protected $table = 'dj_voting_round_dj';
}
