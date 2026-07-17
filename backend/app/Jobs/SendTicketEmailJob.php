<?php

namespace App\Jobs;

use App\Models\SiteSetting;
use App\Models\SoldTicket;
use App\Services\EmailService;
use App\Services\PdfService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTicketEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public string $soldTicketId) {}

    public function handle(
        EmailService $emailService,
        PdfService $pdfService,
        QrCodeService $qrCodeService,
    ): void {
        $soldTicket = SoldTicket::findOrFail($this->soldTicketId);

        $pdfContent = $pdfService->generateTicketPdf([
            'ticketId' => $soldTicket->id,
            'name' => $soldTicket->name,
            'surname' => $soldTicket->surname,
            'personalNumber' => $soldTicket->personal_number,
            'eventName' => $soldTicket->event_name,
            'eventDate' => $soldTicket->event_date,
            'location' => $soldTicket->location,
            'amount' => $soldTicket->amount,
            'currency' => 'GEL',
            'qrCode' => $qrCodeService->generate($soldTicket->qr_code),
            'artworkPath' => $this->resolveArtworkPath($soldTicket),
        ]);

        $emailService->sendTicketEmail(
            to: $soldTicket->email,
            name: $soldTicket->name,
            pdfContent: $pdfContent,
            ticketId: $soldTicket->id,
            eventName: $soldTicket->event_name,
        );
    }

    /**
     * Admin-uploaded PDF artwork (Site Settings → "Ticket email PDF"). Joker
     * events get their own image; a missing upload or deleted file renders the
     * ticket without artwork rather than failing the email.
     */
    private function resolveArtworkPath(SoldTicket $soldTicket): ?string
    {
        $settings = SiteSetting::get('ticketPdf', []);

        $relative = $soldTicket->isJokerEvent()
            ? ($settings['jokerArtwork'] ?? null)
            : ($settings['artwork'] ?? null);

        if (!$relative) {
            return null;
        }

        $path = Storage::disk('public')->path($relative);

        return is_file($path) ? $path : null;
    }
}
