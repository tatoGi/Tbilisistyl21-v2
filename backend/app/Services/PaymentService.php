<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * Create an order on the Quipu/CompassPlus gateway.
     *
     * The gateway expects every field wrapped in an `order` object — a flat
     * payload is rejected with `Unrecognized field "amount"`. Callers pass the
     * order-specific fields (amount, description, hppRedirectUrl, …); the
     * shared, gateway-required fields are merged in here.
     *
     * Returns the inner `order` object of the response
     * (`id`, `password`, `hppUrl`, `status`).
     */
    public function createOrder(array $order): array
    {
        $payload = [
            'order' => array_merge([
                'typeRid' => config('services.quipu.type_rid'),
                'currency' => 'GEL',
                'language' => config('services.quipu.hpp_language', 'ka'),
                'initiationEnvKind' => 'Browser',
            ], $order),
        ];

        $response = Http::withOptions($this->tlsOptions())
            ->post(config('services.quipu.api_url'), $payload);

        $response->throw();

        return $response->json('order') ?? [];
    }

    /**
     * Default browser `consumerDevice` block required by the 3DS2 flow. Only the
     * IP and user agent are request-specific; the rest are static hints.
     */
    public function browserConsumerDevice(?string $ip, ?string $userAgent): array
    {
        return [
            'browser' => [
                'javaEnabled' => false,
                'jsEnabled' => true,
                'acceptHeader' => 'application/json',
                'ip' => $ip ?: '127.0.0.1',
                'colorDepth' => '24',
                'screenW' => '1920',
                'screenH' => '1080',
                'tzOffset' => '-240',
                'language' => 'ka-GE',
                'userAgent' => $userAgent ?: '',
            ],
        ];
    }

    /**
     * Fetch order details. The gateway takes the password as a query parameter
     * (not Basic auth) and returns the data nested under `order`.
     */
    public function getOrderDetails(int $orderId, string $password): array
    {
        $url = config('services.quipu.api_url') . '/' . $orderId
            . '?password=' . urlencode($password)
            . '&tokenDetailLevel=1&tranDetailLevel=1';

        $response = Http::withOptions($this->tlsOptions())->get($url);

        $response->throw();

        return $response->json('order') ?? [];
    }

    public function createCallbackHmac(string $internalId): string
    {
        return hash_hmac('sha256', "callback:{$internalId}", $this->getSecret());
    }

    public function verifyCallbackHmac(string $internalId, string $hmac): bool
    {
        $expected = $this->createCallbackHmac($internalId);

        return hash_equals($expected, $hmac);
    }

    public function createRedirectToken(int $pgOrderId, string $collection): string
    {
        $data = base64_encode(json_encode([
            'pgOrderId' => $pgOrderId,
            'collection' => $collection,
        ]));

        $signature = hash_hmac('sha256', $data, $this->getSecret());

        return $data . '.' . $signature;
    }

    public function verifyRedirectToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$data, $signature] = $parts;
        $expected = hash_hmac('sha256', $data, $this->getSecret());

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode(base64_decode($data), true);

        if (!$decoded || !isset($decoded['pgOrderId'], $decoded['collection'])) {
            return null;
        }

        if (!in_array($decoded['collection'], ['soldTickets', 'productOrders'], true)) {
            return null;
        }

        return $decoded;
    }

    public function verifyPaidAmount(float $expectedGel, array $details): bool
    {
        $actual = $this->extractPaidAmount($details);

        if ($actual === null) {
            return false;
        }

        // We send the amount in major units (GEL) but the details endpoint may
        // report it in either GEL or minor units (tetri). Accept either so a
        // correct payment is never rejected, while a genuinely different amount
        // still fails.
        $expectedGel = round($expectedGel, 2);

        return abs($actual - $expectedGel) < 0.01
            || abs($actual - $expectedGel * 100) < 0.01;
    }

    public function extractPaidAmount(array $details): ?float
    {
        foreach (['amount', 'totalAmount', 'orderAmount', 'Amount'] as $key) {
            if (isset($details[$key]) && is_numeric($details[$key])) {
                return (float) $details[$key];
            }
        }

        return null;
    }

    private function getSecret(): string
    {
        return config('app.key');
    }

    private function tlsOptions(): array
    {
        $options = [];

        $cert = config('services.quipu.cert_base64');
        $key = config('services.quipu.key_base64');
        $ca = config('services.quipu.ca_base64');

        if ($cert && $key) {
            $certPath = tempnam(sys_get_temp_dir(), 'pg_cert_');
            file_put_contents($certPath, base64_decode($cert));
            $keyPath = tempnam(sys_get_temp_dir(), 'pg_key_');
            file_put_contents($keyPath, base64_decode($key));

            $options['cert'] = $certPath;
            $options['ssl_key'] = $keyPath;

            if ($ca) {
                $caPath = tempnam(sys_get_temp_dir(), 'pg_ca_');
                file_put_contents($caPath, base64_decode($ca));
                $options['verify'] = $caPath;
            }
        }

        if (config('services.quipu.tls_reject_unauthorized') === false) {
            $options['verify'] = false;
        }

        return $options;
    }
}
