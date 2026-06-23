<?php

namespace App\Actions;

use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Services\QrCodeService;
use Illuminate\Support\Str;

class CreateTicketOrderAction
{
    public function __construct(
        private PaymentService $paymentService,
        private QrCodeService $qrCodeService,
    ) {}

    public function execute(array $data): array
    {
        $ticket = Ticket::findOrFail($data['ticketId']);

        // Check status and quantity
        if ($ticket->status !== 'active' || $ticket->quantity <= 0) {
            return ['error' => 'sold_out', 'status' => 400];
        }

        // Max 3 tickets per personal number
        $paidCount = SoldTicket::where('personal_number', $data['personalNumber'])
            ->where('status', 'paid')
            ->count();

        if ($paidCount >= 3) {
            return ['error' => 'max_tickets_reached', 'status' => 400];
        }

        // Generate internal ID
        $internalId = strtoupper(Str::random(8));

        // Create callback HMAC
        $hmac = $this->paymentService->createCallbackHmac($internalId);

        // Build PG order
        $appUrl = config('app.url');
        $callbackUrl = "{$appUrl}/api/payments/callback?ref={$internalId}&sig={$hmac}";
        $redirectUrl = "{$appUrl}/api/payments/redirect";

        $pgResponse = $this->paymentService->createOrder([
            'amount' => (int) ($ticket->price_gel * 100),
            'description' => $ticket->setLocale('en')->title ?? $ticket->setLocale('ka')->title,
            'merchantId' => config('services.quipu.merchant_id'),
            'callbackUrl' => $callbackUrl,
            'redirectUrl' => $redirectUrl,
        ]);

        // Generate QR
        $qrData = $this->qrCodeService->generateTicketData(
            $internalId,
            $data['personalNumber'],
            $ticket->id,
        );

        // Store pending ticket
        SoldTicket::create([
            'id' => $internalId,
            'personal_number' => $data['personalNumber'],
            'email' => $data['email'],
            'name' => $data['name'],
            'surname' => $data['surname'],
            'amount' => $ticket->price_gel,
            'status' => 'pending',
            'original_ticket_id' => $ticket->id,
            'event_name' => $ticket->setLocale('ka')->title,
            'event_date' => $ticket->event_date,
            'location' => $ticket->location,
            'pg_order_id' => $pgResponse['orderId'],
            'pg_hpp_url' => $pgResponse['hppUrl'],
            'pg_password' => $pgResponse['password'],
            'qr_code' => $qrData,
        ]);

        // Create signed redirect token
        $token = $this->paymentService->createRedirectToken(
            $pgResponse['orderId'],
            'soldTickets',
        );

        return [
            'redirectUrl' => "/api/payments/redirect?token={$token}",
            'status' => 200,
        ];
    }
}
