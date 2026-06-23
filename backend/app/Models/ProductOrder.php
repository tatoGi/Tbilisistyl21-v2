<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ProductOrder extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'product_id', 'product_title', 'size', 'name', 'email',
        'phone', 'amount', 'status', 'pg_order_id', 'pg_password', 'qr_code',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
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
