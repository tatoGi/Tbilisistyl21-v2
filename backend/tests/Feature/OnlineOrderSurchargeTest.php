<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductSize;
use App\Models\SiteSetting;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineOrderSurchargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_order_charges_gross_and_persists_breakdown(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);

        $ticket = Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 100,
            'quantity' => 10,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('createCallbackHmac')->andReturn('sig');
            $mock->shouldReceive('browserConsumerDevice')->andReturn([]);
            $mock->shouldReceive('createOrder')
                ->once()
                ->withArgs(function (array $payload) {
                    return $payload['amount'] === '103.00';
                })
                ->andReturn(['id' => 1, 'hppUrl' => 'https://pay.test', 'password' => 'x']);
            $mock->shouldReceive('createRedirectToken')->andReturn('token');
        });

        $response = $this->postJson('/api/orders/tickets', [
            'ticketId' => $ticket->id,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'personalNumber' => '12345678901',
        ]);

        $response->assertOk();
        $sold = SoldTicket::first();
        $this->assertEquals(100, (float) $sold->base_amount);
        $this->assertEquals(3, (float) $sold->surcharge_amount);
        $this->assertEquals(3, (float) $sold->surcharge_rate);
        $this->assertEquals(103, (float) $sold->amount);
    }

    public function test_product_order_charges_gross_and_persists_breakdown(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 2.5]);

        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-Shirt'],
            'price_gel' => 40,
            'status' => 'active',
        ]);

        ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => 5,
        ]);

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('createCallbackHmac')->andReturn('sig');
            $mock->shouldReceive('browserConsumerDevice')->andReturn([]);
            $mock->shouldReceive('createOrder')
                ->once()
                ->withArgs(function (array $payload) {
                    return $payload['amount'] === '41.00';
                })
                ->andReturn(['id' => 2, 'hppUrl' => 'https://pay.test', 'password' => 'x']);
            $mock->shouldReceive('createRedirectToken')->andReturn('token');
        });

        $response = $this->postJson('/api/orders/products', [
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'John',
            'surname' => 'Doe',
            'personalNumber' => '12345678901',
            'email' => 'john@test.com',
            'phone' => '555123456',
        ]);

        $response->assertOk();
        $order = ProductOrder::first();
        $this->assertEquals(40, (float) $order->base_amount);
        $this->assertEquals(1, (float) $order->surcharge_amount);
        $this->assertEquals(2.5, (float) $order->surcharge_rate);
        $this->assertEquals(41, (float) $order->amount);
    }
}
