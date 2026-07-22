<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SoldTicket extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'personal_number', 'email', 'name', 'surname', 'amount',
        'status', 'original_ticket_id', 'event_name', 'event_date',
        'location', 'paid_at', 'scanned_at', 'scanned_by',
        'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
        'failed_at', 'fail_reason', 'is_joker', 'is_techno',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'event_date' => 'date',
            'paid_at' => 'datetime',
            'scanned_at' => 'datetime',
            'failed_at' => 'datetime',
            'is_joker' => 'boolean',
            'is_techno' => 'boolean',
        ];
    }

    public function isJokerEvent(): bool
    {
        // The explicit admin checkbox is authoritative; the name check keeps
        // legacy rows working (both latin and Georgian spellings).
        return (bool) $this->is_joker
            || ($this->event_name !== null
                && (str_contains(strtolower($this->event_name), 'joker')
                    || str_contains($this->event_name, 'ჯოკერ')));
    }

    public function isTechnoEvent(): bool
    {
        // Mirrors isJokerEvent(): explicit admin toggle is authoritative, with
        // a name fallback (latin "techno" and Georgian "ტექნო") for legacy rows.
        return (bool) $this->is_techno
            || ($this->event_name !== null
                && (str_contains(strtolower($this->event_name), 'techno')
                    || str_contains($this->event_name, 'ტექნო')));
    }

    public function setPgPasswordAttribute(?string $value): void
    {
        $this->attributes['pg_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPgPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setQrCodeAttribute(?string $value): void
    {
        $this->attributes['qr_code'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getQrCodeAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
