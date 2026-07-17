<?php

namespace Tests\Feature;

use App\Actions\CreateProductOrderAction;
use App\Jobs\SendProductOrderEmailJob;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductSize;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProductOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithOrder(string $status = 'pending'): array
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-Shirt'],
            'price_gel' => 80,
            'status' => 'active',
        ]);

        $size = ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => 5,
        ]);

        $order = ProductOrder::create([
            'id' => 'PROD0001',
            'product_id' => $product->id,
            'product_title' => 'მაისური',
            'size' => 'M',
            'name' => 'John',
            'email' => 'test@test.com',
            'phone' => '555123456',
            'amount' => 80,
            'status' => $status,
            'pg_order_id' => 444,
            'pg_password' => 'secret123',
        ]);

        return [$product, $size, $order];
    }

    public function test_create_product_order_redirect_token_carries_gateway_order_id(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-Shirt'],
            'price_gel' => 80,
            'status' => 'active',
        ]);

        ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => 5,
        ]);

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'id' => 444,
                    'password' => 'pw',
                    'hppUrl' => 'https://pg.test/hpp',
                    'status' => 'Preparing',
                ]);
        });

        $result = app(CreateProductOrderAction::class)->execute([
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'John',
            'surname' => 'Doe',
            'personalNumber' => '01001234567',
            'email' => 'test@test.com',
            'phone' => '555123456',
        ]);

        $this->assertEquals(200, $result['status']);

        parse_str(parse_url($result['redirectUrl'], PHP_URL_QUERY), $query);
        $decoded = app(PaymentService::class)->verifyRedirectToken($query['token']);

        $this->assertNotNull($decoded);
        $this->assertEquals(444, $decoded['pgOrderId']);
        $this->assertEquals('productOrders', $decoded['collection']);
    }

    public function test_payment_callback_marks_product_order_paid(): void
    {
        Bus::fake([SendProductOrderEmailJob::class]);

        [$product, $size, $order] = $this->createProductWithOrder();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('getOrderDetails')
                ->once()
                ->andReturn(['status' => 'paid', 'amount' => 80]);
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get('/api/payments/callback?ref=PROD0001&ID=444&sig=valid');
        $response->assertRedirect();
        $this->assertStringContainsString('success', $response->headers->get('Location'));

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $size->refresh();
        $this->assertEquals(4, $size->quantity);

        Bus::assertDispatched(SendProductOrderEmailJob::class, function ($job) {
            return $job->productOrderId === 'PROD0001';
        });
    }

    public function test_payment_callback_marks_product_order_failed_on_declined(): void
    {
        [$product, $size, $order] = $this->createProductWithOrder();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('getOrderDetails')
                ->once()
                ->andReturn(['status' => 'refused']);
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get('/api/payments/callback?ref=PROD0001&ID=444&sig=valid');
        $response->assertRedirect();
        $this->assertStringContainsString('fail', $response->headers->get('Location'));

        $order->refresh();
        $this->assertEquals('failed', $order->status);

        $size->refresh();
        $this->assertEquals(5, $size->quantity);
    }

    public function test_product_order_callback_idempotent_for_already_paid(): void
    {
        [$product, $size, $order] = $this->createProductWithOrder('paid');

        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldNotReceive('getOrderDetails');
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get('/api/payments/callback?ref=PROD0001&ID=444&sig=valid');
        $response->assertRedirect();
        $this->assertStringContainsString('success', $response->headers->get('Location'));

        $size->refresh();
        $this->assertEquals(5, $size->quantity);
    }

    public function test_product_order_callback_rejects_amount_mismatch(): void
    {
        [$product, $size, $order] = $this->createProductWithOrder();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('getOrderDetails')
                ->once()
                ->andReturn(['status' => 'paid', 'amount' => 999]);
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get('/api/payments/callback?ref=PROD0001&ID=444&sig=valid');
        $response->assertRedirect();
        $this->assertStringContainsString('fail', $response->headers->get('Location'));

        $order->refresh();
        $this->assertEquals('failed', $order->status);

        $size->refresh();
        $this->assertEquals(5, $size->quantity);
    }
}
