<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JokerTicket extends Model
{
    use HasUuids;

    protected $fillable = ['sold_ticket_id', 'personal_number', 'email', 'name', 'surname'];

    public function soldTicket(): BelongsTo
    {
        return $this->belongsTo(SoldTicket::class);
    }
}
