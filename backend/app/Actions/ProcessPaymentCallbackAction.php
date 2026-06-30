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
            return ['status' => 200];
        }

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

        if (!$this->paymentService->verifyPaidAmount((float) $soldTicket->amount, $details)) {
            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'amount_mismatch',
            ]);

            return ['error' => 'amount_mismatch', 'status' => 400];
        }

        return DB::transaction(function () use ($soldTicket) {
            $locked = SoldTicket::where('id', $soldTicket->id)->lockForUpdate()->first();

            if ($locked->status === 'paid') {
                return ['status' => 200];
            }

            $maxTickets = config('app.max_tickets_per_person', 3);

            $paidCount = SoldTicket::where('personal_number', $locked->personal_number)
                ->where('status', 'paid')
                ->where('id', '!=', $locked->id)
                ->lockForUpdate()
                ->get()
                ->count();

            if ($paidCount >= $maxTickets) {
                $locked->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'fail_reason' => 'max_tickets_reached',
                ]);

                return ['error' => 'max_tickets_reached', 'status' => 400];
            }

            $decremented = DB::table('tickets')
                ->where('id', $locked->original_ticket_id)
                ->where('status', 'active')
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                $locked->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'fail_reason' => 'sold_out',
                ]);

                return ['error' => 'sold_out', 'status' => 400];
            }

            $ticket = Ticket::find($locked->original_ticket_id);
            if ($ticket && $ticket->quantity <= 0) {
                $ticket->update(['status' => 'sold_out']);
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($this->isJokerTicket($locked->event_name)) {
                JokerTicket::create([
                    'sold_ticket_id' => $locked->id,
                    'personal_number' => $locked->personal_number,
                    'email' => $locked->email,
                    'name' => $locked->name,
                    'surname' => $locked->surname,
                ]);
            }

            SendTicketEmailJob::dispatch($locked->id);

            return ['status' => 200];
        });
    }

    private function isJokerTicket(?string $eventName): bool
    {
        if (!$eventName) {
            return false;
        }

        return str_contains(strtolower($eventName), 'joker');
    }
}
