<?php

namespace App\Jobs;

use App\Models\ProductOrder;
use App\Services\EmailService;
use App\Services\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendProductOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public string $productOrderId) {}

    public function handle(EmailService $emailService): void
    {
        $order = ProductOrder::findOrFail($this->productOrderId);

        $qrPng = (new QrCodeService())->generate(json_encode([
            'orderId' => $order->id,
            'productTitle' => $order->product_title,
            'size' => $order->size,
        ]));

        $emailService->sendProductOrderEmail(
            to: $order->email,
            name: $order->name,
            qrPng: $qrPng,
            orderId: $order->id,
            productTitle: $order->product_title,
            size: $order->size,
        );
    }
}
