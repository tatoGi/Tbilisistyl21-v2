<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Services\PaymentSurchargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSurchargeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_rate_is_three_percent(): void
    {
        $svc = app(PaymentSurchargeService::class);
        $this->assertSame(3.0, $svc->rate());
    }

    public function test_breakdown_rounds_half_up_to_two_decimals(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);
        $b = app(PaymentSurchargeService::class)->breakdown(100.00);
        $this->assertSame(100.0, $b['base_amount']);
        $this->assertSame(3.0, $b['surcharge_rate']);
        $this->assertSame(3.0, $b['surcharge_amount']);
        $this->assertSame(103.0, $b['amount']);
    }

    public function test_breakdown_uses_configured_rate(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 2.5]);
        $b = app(PaymentSurchargeService::class)->breakdown(40.00);
        $this->assertSame(1.0, $b['surcharge_amount']);
        $this->assertSame(41.0, $b['amount']);
    }

    public function test_payable_matches_breakdown_amount(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);
        $svc = app(PaymentSurchargeService::class);
        $this->assertSame($svc->breakdown(50)['amount'], $svc->payable(50));
    }
}
