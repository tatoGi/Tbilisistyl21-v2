<?php

namespace App\Actions;

use App\Models\SoldTicket;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;

class ValidateTicketAction
{
    public function __construct(private QrCodeService $qrCodeService) {}

    public function execute(array $qrData): array
    {
        if (!$this->qrCodeService->verifyPayload($qrData)) {
            return ['error' => 'invalid_qr_signature', 'status' => 400];
        }

        $ticketId = $qrData['ticketId'];
        $personalNumber = $qrData['personalNumber'];

        $soldTicket = SoldTicket::find($ticketId);

        if (!$soldTicket) {
            return ['error' => 'ticket_not_found', 'status' => 404];
        }

        if ($soldTicket->personal_number !== $personalNumber) {
            return ['error' => 'ticket_not_found', 'status' => 404];
        }

        if ($soldTicket->status !== 'paid') {
            return ['error' => 'ticket_not_paid', 'status' => 400];
        }

        if ($soldTicket->scanned_at) {
            return ['error' => 'already_scanned', 'scannedAt' => $soldTicket->scanned_at, 'status' => 409];
        }

        // Atomic scan
        $updated = DB::table('sold_tickets')
            ->where('id', $ticketId)
            ->whereNull('scanned_at')
            ->update([
                'scanned_at' => now(),
                'scanned_by' => 'admin',
                'status' => 'scanned',
            ]);

        if ($updated === 0) {
            return ['error' => 'already_scanned', 'status' => 409];
        }

        $soldTicket->refresh();

        return [
            'ticket' => [
                'id' => $soldTicket->id,
                'name' => $soldTicket->name,
                'surname' => $soldTicket->surname,
                'personalNumber' => $soldTicket->personal_number,
                'eventName' => $soldTicket->event_name,
                'eventDate' => $soldTicket->event_date,
                'amount' => $soldTicket->amount,
                'paidAt' => $soldTicket->paid_at,
            ],
            'status' => 200,
        ];
    }
}
