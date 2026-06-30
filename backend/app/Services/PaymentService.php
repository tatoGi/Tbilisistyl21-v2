<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function createOrder(array $body): array
    {
        $response = Http::withOptions($this->tlsOptions())
            ->post(config('services.quipu.api_url'), array_merge($body, [
                'typeRid' => config('services.quipu.type_rid'),
            ]));

        $response->throw();

        return $response->json();
    }

    public function getOrderDetails(int $orderId, string $password): array
    {
        $url = config('services.quipu.api_url') . '/' . $orderId;

        $response = Http::withOptions($this->tlsOptions())
            ->withHeaders(['Authorization' => 'Basic ' . base64_encode($orderId . ':' . $password)])
            ->get($url);

        $response->throw();

        return $response->json();
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
        $expectedTetri = (int) round($expectedGel * 100);
        $actualTetri = $this->extractPaidAmountTetri($details);

        if ($actualTetri === null) {
            return false;
        }

        return $actualTetri === $expectedTetri;
    }

    public function extractPaidAmountTetri(array $details): ?int
    {
        foreach (['amount', 'totalAmount', 'orderAmount', 'Amount'] as $key) {
            if (isset($details[$key]) && is_numeric($details[$key])) {
                return (int) $details[$key];
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
