<?php

namespace Tests\Unit;

use App\Services\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    public function test_create_callback_hmac_produces_hex_string(): void
    {
        $hmac = $this->service->createCallbackHmac('TEST1234');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hmac);
    }

    public function test_verify_callback_hmac_returns_true_for_valid(): void
    {
        $hmac = $this->service->createCallbackHmac('TEST1234');
        $this->assertTrue($this->service->verifyCallbackHmac('TEST1234', $hmac));
    }

    public function test_verify_callback_hmac_returns_false_for_invalid(): void
    {
        $this->assertFalse($this->service->verifyCallbackHmac('TEST1234', 'invalid'));
    }

    public function test_create_and_verify_redirect_token(): void
    {
        $token = $this->service->createRedirectToken(12345, 'soldTickets');
        $result = $this->service->verifyRedirectToken($token);

        $this->assertNotNull($result);
        $this->assertEquals(12345, $result['pgOrderId']);
        $this->assertEquals('soldTickets', $result['collection']);
    }

    public function test_verify_redirect_token_returns_null_for_tampered(): void
    {
        $token = $this->service->createRedirectToken(12345, 'soldTickets');
        $this->assertNull($this->service->verifyRedirectToken($token . 'x'));
    }
}
