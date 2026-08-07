<?php

namespace App\Actions;

use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTicketOrderAction
{
    public function __construct(
        private PaymentService $paymentService,
        private QrCodeService $qrCodeService,
    ) {}

    public function execute(array $data): array
    {
        $allowed = config('app.purchase_allowed_personal_numbers');
        if ($allowed && !in_array($data['personalNumber'], $allowed, true)) {
            return ['error' => 'ბილეთის ყიდვა დროებით შეზღუდულია.', 'status' => 403];
        }

        return DB::transaction(function () use ($data) {
            $ticket = Ticket::where('id', $data['ticketId'])->lockForUpdate()->firstOrFail();

            if ($ticket->status !== 'active' || $ticket->quantity <= 0) {
                return ['error' => 'sold_out', 'status' => 400];
            }

            $reservedCount = SoldTicket::where('personal_number', $data['personalNumber'])
                ->whereIn('status', ['paid', 'pending'])
                ->lockForUpdate()
                ->get()
                ->count();

            if ($reservedCount >= config('app.max_tickets_per_person', 3)) {
                return ['error' => 'max_tickets_reached', 'status' => 400];
            }

            $internalId = strtoupper(Str::random(8));
            $hmac = $this->paymentService->createCallbackHmac($internalId);

            $appUrl = config('app.url');
            $callbackUrl = "{$appUrl}/api/payments/callback?ref={$internalId}&sig={$hmac}";

            $pgResponse = $this->paymentService->createOrder([
                'amount' => number_format((float) $ticket->price_gel, 2, '.', ''),
                'description' => $ticket->setLocale('en')->title ?? $ticket->setLocale('ka')->title,
                'hppRedirectUrl' => $callbackUrl,
                'consumerDevice' => $this->paymentService->browserConsumerDevice(
                    $data['ip'] ?? null,
                    $data['userAgent'] ?? null,
                ),
            ]);

            if (empty($pgResponse['id'])) {
                return ['error' => 'payment_gateway_error', 'status' => 502];
            }

            $qrData = $this->qrCodeService->generateTicketData(
                $internalId,
                $data['personalNumber'],
                $ticket->id,
            );

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
                'is_joker' => $ticket->is_joker,
                'is_techno' => $ticket->is_techno,
                'event_date' => $ticket->event_date,
                'location' => $ticket->location,
                'pg_order_id' => $pgResponse['id'],
                'pg_hpp_url' => $pgResponse['hppUrl'] ?? '',
                'pg_password' => $pgResponse['password'] ?? '',
                'qr_code' => $qrData,
            ]);

            $token = $this->paymentService->createRedirectToken(
                $pgResponse['id'],
                'soldTickets',
            );

            return [
                'redirectUrl' => "/api/payments/redirect?token={$token}",
                'status' => 200,
            ];
        });
    }
}
