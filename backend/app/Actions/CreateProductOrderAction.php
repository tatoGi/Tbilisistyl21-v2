<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductSize;
use App\Services\PaymentService;
use Illuminate\Support\Str;

class CreateProductOrderAction
{
    public function __construct(private PaymentService $paymentService) {}

    public function execute(array $data): array
    {
        $product = Product::with('sizes')->findOrFail($data['productId']);

        if ($product->status !== 'active') {
            return ['error' => 'product_unavailable', 'status' => 400];
        }

        $size = $product->sizes->where('size', $data['size'])->first();

        if (!$size || $size->quantity <= 0) {
            return ['error' => 'size_sold_out', 'status' => 400];
        }

        $internalId = strtoupper(Str::random(8));
        $hmac = $this->paymentService->createCallbackHmac($internalId);

        $appUrl = config('app.url');
        $callbackUrl = "{$appUrl}/api/payments/callback?ref={$internalId}&sig={$hmac}";

        $pgResponse = $this->paymentService->createOrder([
            'amount' => number_format((float) $product->price_gel, 2, '.', ''),
            'description' => $product->setLocale('en')->title ?? $product->setLocale('ka')->title,
            'hppRedirectUrl' => $callbackUrl,
            'consumerDevice' => $this->paymentService->browserConsumerDevice(
                $data['ip'] ?? null,
                $data['userAgent'] ?? null,
            ),
        ]);

        if (empty($pgResponse['id'])) {
            return ['error' => 'payment_gateway_error', 'status' => 502];
        }

        ProductOrder::create([
            'id' => $internalId,
            'product_id' => $product->id,
            'product_title' => $product->setLocale('ka')->title,
            'size' => $data['size'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'amount' => $product->price_gel,
            'status' => 'pending',
            'pg_order_id' => $pgResponse['id'],
            'pg_hpp_url' => $pgResponse['hppUrl'] ?? '',
            'pg_password' => $pgResponse['password'] ?? '',
        ]);

        $token = $this->paymentService->createRedirectToken(
            $pgResponse['orderId'],
            'productOrders',
        );

        return [
            'redirectUrl' => "/api/payments/redirect?token={$token}",
            'status' => 200,
        ];
    }
}
