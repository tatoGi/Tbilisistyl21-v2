<?php

namespace App\Actions;

use App\Jobs\SendTicketEmailJob;
use App\Models\JokerTicket;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

class ProcessPaymentCallbackAction
{
    public function __construct(private PaymentService $paymentService) {}

    public function execute(string $ref, int $pgOrderId): array
    {
        $soldTicket = SoldTicket::where('id', $ref)
            ->where('pg_order_id', $pgOrderId)
            ->first();

        if (!$soldTicket) {
            return ['error' => 'ticket_not_found', 'status' => 404];
        }

        if ($soldTicket->status === 'paid') {
            return ['ticketId' => $ref, 'status' => 200];
        }

        // Check payment with Quipu
        $details = $this->paymentService->getOrderDetails(
            $soldTicket->pg_order_id,
            $soldTicket->pg_password,
        );

        $pgStatus = strtolower($details['status'] ?? '');
        $isPaid = in_array($pgStatus, ['paid', 'completed', 'fullypaid'], true);

        if (!$isPaid) {
            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'payment_' . $pgStatus,
            ]);
            return ['error' => 'payment_failed', 'status' => 400];
        }

        // Atomic inventory decrement
        $decremented = DB::table('tickets')
            ->where('id', $soldTicket->original_ticket_id)
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->decrement('quantity');

        if ($decremented === 0) {
            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'sold_out',
            ]);
            return ['error' => 'sold_out', 'status' => 400];
        }

        // Check if last unit — mark sold_out
        $ticket = Ticket::find($soldTicket->original_ticket_id);
        if ($ticket && $ticket->quantity <= 0) {
            $ticket->update(['status' => 'sold_out']);
        }

        // Mark paid
        $soldTicket->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Joker ticket handling
        if ($this->isJokerTicket($soldTicket->event_name)) {
            JokerTicket::create([
                'sold_ticket_id' => $soldTicket->id,
                'personal_number' => $soldTicket->personal_number,
                'email' => $soldTicket->email,
                'name' => $soldTicket->name,
                'surname' => $soldTicket->surname,
            ]);
        }

        // Queue email
        SendTicketEmailJob::dispatch($soldTicket->id);

        return ['ticketId' => $ref, 'status' => 200];
    }

    private function isJokerTicket(?string $eventName): bool
    {
        if (!$eventName) return false;
        return str_contains(strtolower($eventName), 'joker');
    }
}
