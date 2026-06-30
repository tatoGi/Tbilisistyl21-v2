<?php

namespace App\Console\Commands;

use App\Models\SoldTicket;
use App\Services\QrCodeService;
use Illuminate\Console\Command;

class RefreshTicketQrSignatures extends Command
{
    protected $signature = 'tickets:refresh-qr {--status=paid : Comma-separated sold ticket statuses to refresh}';

    protected $description = 'Re-sign sold ticket QR payloads (v2 HMAC) for gate scanners';

    public function handle(QrCodeService $qrCodeService): int
    {
        $statuses = array_map('trim', explode(',', $this->option('status')));
        $updated = 0;
        $skipped = 0;

        SoldTicket::query()
            ->whereIn('status', $statuses)
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use ($qrCodeService, &$updated, &$skipped) {
                foreach ($tickets as $ticket) {
                    if (!$ticket->original_ticket_id) {
                        $skipped++;
                        continue;
                    }

                    $ticket->qr_code = $qrCodeService->generateTicketData(
                        $ticket->id,
                        $ticket->personal_number,
                        $ticket->original_ticket_id,
                    );
                    $ticket->save();
                    $updated++;
                }
            });

        $this->info("Updated {$updated} ticket QR payload(s). Skipped {$skipped} without event reference.");

        return self::SUCCESS;
    }
}
