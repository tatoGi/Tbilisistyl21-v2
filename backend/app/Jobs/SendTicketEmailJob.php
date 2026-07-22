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
            // Techno artwork is a portrait poster; letterbox it instead of
            // forcing the joker/standard 1:1 square so it isn't distorted.
            'artworkContain' => $soldTicket->isTechnoEvent(),
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
     * Resolves the artwork embedded in the ticket PDF.
     *
     * Techno tickets use a built-in image shipped with the app (no admin upload
     * required). Joker / standard tickets use the images uploaded in Site
     * Settings → "Ticket email PDF". A missing upload or deleted file renders
     * the ticket without artwork rather than failing the email.
     */
    private function resolveArtworkPath(SoldTicket $soldTicket): ?string
    {
        if ($soldTicket->isTechnoEvent()) {
            $path = resource_path('artwork/techno-ticket.png');

            return is_file($path) ? $path : null;
        }

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
