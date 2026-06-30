<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public const QR_VERSION = 2;

    public function generate(string $data): string
    {
        $png = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($data);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function generateTicketData(string $ticketId, string $personalNumber, string $eventId): string
    {
        $payload = [
            'ticketId' => $ticketId,
            'personalNumber' => $personalNumber,
            'eventId' => $eventId,
            'timestamp' => now()->toISOString(),
            'version' => self::QR_VERSION,
        ];

        $payload['sig'] = $this->signPayload(
            $payload['ticketId'],
            $payload['personalNumber'],
            $payload['eventId'],
        );

        return json_encode($payload);
    }

    public function signPayload(string $ticketId, string $personalNumber, string $eventId): string
    {
        return hash_hmac(
            'sha256',
            "ticket:{$ticketId}:{$personalNumber}:{$eventId}",
            $this->getSecret(),
        );
    }

    public function verifyPayload(array $data): bool
    {
        $ticketId = $data['ticketId'] ?? null;
        $personalNumber = $data['personalNumber'] ?? null;
        $eventId = $data['eventId'] ?? null;
        $sig = $data['sig'] ?? null;
        $version = (int) ($data['version'] ?? 0);

        if (!$ticketId || !$personalNumber || !$eventId || !$sig || $version < self::QR_VERSION) {
            return false;
        }

        $expected = $this->signPayload($ticketId, $personalNumber, $eventId);

        return hash_equals($expected, $sig);
    }

    private function getSecret(): string
    {
        return config('app.key');
    }
}
