<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
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
        return json_encode([
            'ticketId' => $ticketId,
            'personalNumber' => $personalNumber,
            'eventId' => $eventId,
            'timestamp' => now()->toISOString(),
            'version' => 1,
        ]);
    }
}
