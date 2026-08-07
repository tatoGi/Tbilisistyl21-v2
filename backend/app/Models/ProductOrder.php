<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ProductOrder extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'product_id', 'product_title', 'size', 'name', 'surname', 'personal_number',
        'email', 'phone', 'amount', 'base_amount', 'surcharge_amount', 'surcharge_rate',
        'status', 'paid_at', 'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
        'sold_by', 'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'surcharge_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
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
