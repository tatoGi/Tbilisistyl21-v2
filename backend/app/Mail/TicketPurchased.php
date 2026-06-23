<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $pdfContent,
        public string $ticketId,
        public ?string $eventName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->eventName
                ? "Your Ticket - {$this->eventName}"
                : 'Your Ticket - TbilisiStyle21',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-purchased',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "ticket-{$this->ticketId}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
