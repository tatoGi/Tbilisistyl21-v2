<?php

namespace App\Services;

use App\Models\SiteSetting;

class PaymentSurchargeService
{
    public const DEFAULT_PERCENT = 3.0;

    public function rate(): float
    {
        $raw = SiteSetting::get('payment_surcharge_percent', ['percent' => self::DEFAULT_PERCENT]);

        if (is_numeric($raw)) {
            return round((float) $raw, 2);
        }

        if (is_array($raw) && isset($raw['percent']) && is_numeric($raw['percent'])) {
            return round((float) $raw['percent'], 2);
        }

        return self::DEFAULT_PERCENT;
    }

    /**
     * @return array{base_amount: float, surcharge_rate: float, surcharge_amount: float, amount: float}
     */
    public function breakdown(float $baseAmount): array
    {
        $base = round($baseAmount, 2);
        $rate = $this->rate();
        $surcharge = round($base * $rate / 100, 2);

        return [
            'base_amount' => $base,
            'surcharge_rate' => $rate,
            'surcharge_amount' => $surcharge,
            'amount' => round($base + $surcharge, 2),
        ];
    }

    public function payable(float $baseAmount): float
    {
        return $this->breakdown($baseAmount)['amount'];
    }
}
