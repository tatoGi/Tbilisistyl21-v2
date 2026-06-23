<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductOrderConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $qrPng,
        public string $orderId,
        public string $productTitle,
        public string $size,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order Confirmed - {$this->productTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.product-order-confirmed',
        );
    }
}
