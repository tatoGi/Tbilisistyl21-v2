<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    public function test_ticket_payload_signature_roundtrip(): void
    {
        $service = app(QrCodeService::class);

        $json = $service->generateTicketData(
            'ABCD1234',
            '12345678901',
            '11111111-1111-1111-1111-111111111111',
        );

        $payload = json_decode($json, true);

        $this->assertSame(QrCodeService::QR_VERSION, $payload['version']);
        $this->assertTrue($service->verifyPayload($payload));
    }

    public function test_tampered_payload_is_rejected(): void
    {
        $service = app(QrCodeService::class);

        $json = $service->generateTicketData(
            'ABCD1234',
            '12345678901',
            '11111111-1111-1111-1111-111111111111',
        );

        $payload = json_decode($json, true);
        $payload['personalNumber'] = '99999999999';

        $this->assertFalse($service->verifyPayload($payload));
    }
}
