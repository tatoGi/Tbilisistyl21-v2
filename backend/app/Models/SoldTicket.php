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
        'failed_at', 'fail_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'event_date' => 'date',
            'paid_at' => 'datetime',
            'scanned_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function isJokerEvent(): bool
    {
        return $this->event_name !== null
            && str_contains(strtolower($this->event_name), 'joker');
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
