<?php

namespace App\Actions;

use App\Jobs\SendTicketEmailJob;
use App\Models\JokerTicket;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWalkUpTicketSaleAction
{
    public function __construct(private QrCodeService $qrCodeService) {}

    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::where('id', $data['ticketId'])->lockForUpdate()->firstOrFail();

            if ($ticket->status !== 'active' || $ticket->quantity <= 0) {
                return ['error' => 'sold_out', 'status' => 400];
            }

            $decremented = DB::table('tickets')
                ->where('id', $ticket->id)
                ->where('status', 'active')
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                return ['error' => 'sold_out', 'status' => 400];
            }

            $ticket->refresh();
            if ($ticket->quantity <= 0) {
                $ticket->update(['status' => 'sold_out']);
            }

            $discount = (float) ($data['discountAmount'] ?? 0);
            $finalAmount = max(0, (float) $ticket->price_gel - $discount);

            $internalId = strtoupper(Str::random(8));

            $qrData = $this->qrCodeService->generateTicketData(
                $internalId,
                $data['personalNumber'],
                $ticket->id,
            );

            $soldTicket = SoldTicket::create([
                'id' => $internalId,
                'personal_number' => $data['personalNumber'],
                'email' => $data['email'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'amount' => $finalAmount,
                'discount_amount' => $discount > 0 ? $discount : null,
                'sold_by' => $data['soldBy'],
                'status' => 'paid',
                'paid_at' => now(),
                'original_ticket_id' => $ticket->id,
                'event_name' => $ticket->setLocale('ka')->title,
                'is_joker' => $ticket->is_joker,
                'is_techno' => $ticket->is_techno,
                'event_date' => $ticket->event_date,
                'location' => $ticket->location,
                'qr_code' => $qrData,
            ]);

            if ($soldTicket->isJokerEvent()) {
                JokerTicket::create([
                    'sold_ticket_id' => $soldTicket->id,
                    'personal_number' => $soldTicket->personal_number,
                    'email' => $soldTicket->email,
                    'name' => $soldTicket->name,
                    'surname' => $soldTicket->surname,
                ]);
            }

            SendTicketEmailJob::dispatch($soldTicket->id);

            return ['soldTicket' => $soldTicket, 'status' => 200];
        });
    }
}
