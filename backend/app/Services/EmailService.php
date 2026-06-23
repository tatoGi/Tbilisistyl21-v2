<?php

namespace App\Services;

use App\Mail\ProductOrderConfirmed;
use App\Mail\TicketPurchased;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendTicketEmail(
        string $to,
        string $name,
        string $pdfContent,
        string $ticketId,
        ?string $eventName = null
    ): void {
        Mail::to($to)->send(new TicketPurchased(
            name: $name,
            pdfContent: $pdfContent,
            ticketId: $ticketId,
            eventName: $eventName,
        ));
    }

    public function sendProductOrderEmail(
        string $to,
        string $name,
        string $qrPng,
        string $orderId,
        string $productTitle,
        string $size
    ): void {
        Mail::to($to)->send(new ProductOrderConfirmed(
            name: $name,
            qrPng: $qrPng,
            orderId: $orderId,
            productTitle: $productTitle,
            size: $size,
        ));
    }
}
