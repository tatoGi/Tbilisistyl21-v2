<?php

namespace Tests\Feature;

use App\Jobs\SendTicketEmailJob;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveTicketAndSoldTicket(string $status = 'pending'): array
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'ტესტი', 'en' => 'Test'],
            'price_gel' => 50,
            'quantity' => 10,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $soldTicket = SoldTicket::create([
            'id' => 'ABCD1234',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => $status,
            'original_ticket_id' => $ticket->id,
            'event_name' => 'Test Concert',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'pg_order_id' => 999,
            'pg_password' => 'secret123',
        ]);

        return [$ticket, $soldTicket];
    }

    private function mockPaidPaymentGateway(array $details = ['status' => 'paid', 'amount' => 5000]): void
    {
        $this->partialMock(PaymentService::class, function ($mock) use ($details) {
            $mock->shouldReceive('getOrderDetails')
                ->once()
                ->andReturn($details);
            $mock->shouldReceive('verifyCallbackHmac')
                ->andReturn(true);
        });
    }

    public function test_payment_callback_marks_ticket_paid_on_success(): void
    {
        Bus::fake([SendTicketEmailJob::class]);

        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket();

        $this->mockPaidPaymentGateway();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $response->assertRedirect();

        $soldTicket->refresh();
        $this->assertEquals('paid', $soldTicket->status);
        $this->assertNotNull($soldTicket->paid_at);

        // Verify inventory decremented
        $ticket->refresh();
        $this->assertEquals(9, $ticket->quantity);

        // Verify email job was dispatched
        Bus::assertDispatched(SendTicketEmailJob::class, function ($job) {
            return $job->soldTicketId === 'ABCD1234';
        });
    }

    public function test_payment_callback_marks_ticket_failed_on_bad_payment(): void
    {
        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('getOrderDetails')
                ->once()
                ->andReturn(['status' => 'declined']);
            $mock->shouldReceive('verifyCallbackHmac')
                ->andReturn(true);
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $response->assertRedirect();

        $soldTicket->refresh();
        $this->assertEquals('failed', $soldTicket->status);
        $this->assertNotNull($soldTicket->failed_at);
        $this->assertEquals('payment_declined', $soldTicket->fail_reason);

        // Quantity unchanged
        $ticket->refresh();
        $this->assertEquals(10, $ticket->quantity);
    }

    public function test_payment_callback_idempotent_for_already_paid(): void
    {
        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket('paid');
        $soldTicket->update(['paid_at' => now()]);

        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('verifyCallbackHmac')
                ->andReturn(true);
            $mock->shouldNotReceive('getOrderDetails');
        });

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $response->assertRedirect();

        // Quantity unchanged
        $ticket->refresh();
        $this->assertEquals(10, $ticket->quantity);
    }

    public function test_payment_callback_handles_sold_out_during_payment(): void
    {
        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket();

        // Set quantity to 0 to simulate race condition
        $ticket->update(['quantity' => 0, 'status' => 'sold_out']);

        $this->mockPaidPaymentGateway();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $response->assertRedirect();

        $soldTicket->refresh();
        $this->assertEquals('failed', $soldTicket->status);
        $this->assertEquals('sold_out', $soldTicket->fail_reason);
    }

    public function test_last_ticket_marks_event_sold_out(): void
    {
        Bus::fake([SendTicketEmailJob::class]);

        $ticket = Ticket::create([
            'title' => ['ka' => 'ტესტი'],
            'price_gel' => 50,
            'quantity' => 1,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        SoldTicket::create([
            'id' => 'LAST0001',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'original_ticket_id' => $ticket->id,
            'event_name' => 'Test Concert',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'pg_order_id' => 888,
            'pg_password' => 'secret',
        ]);

        $this->mockPaidPaymentGateway();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $this->get("/api/payments/callback?ref=LAST0001&ID=888&sig=valid");

        $ticket->refresh();
        $this->assertEquals(0, $ticket->quantity);
        $this->assertEquals('sold_out', $ticket->status);
    }

    public function test_payment_callback_rejects_amount_mismatch(): void
    {
        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket();

        $this->mockPaidPaymentGateway(['status' => 'paid', 'amount' => 100]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $response->assertRedirect();

        $soldTicket->refresh();
        $this->assertEquals('failed', $soldTicket->status);
        $this->assertEquals('amount_mismatch', $soldTicket->fail_reason);
    }

    public function test_payment_callback_rejects_fourth_paid_ticket(): void
    {
        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket();

        for ($i = 0; $i < 3; $i++) {
            SoldTicket::create([
                'id' => 'PD' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'personal_number' => '12345678901',
                'email' => 'test@test.com',
                'name' => 'John',
                'surname' => 'Doe',
                'amount' => 50,
                'status' => 'paid',
                'paid_at' => now(),
                'event_name' => 'Test',
                'event_date' => '2026-08-01',
                'location' => 'Tbilisi',
            ]);
        }

        $this->mockPaidPaymentGateway();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $response->assertRedirect();

        $soldTicket->refresh();
        $this->assertEquals('failed', $soldTicket->status);
        $this->assertEquals('max_tickets_reached', $soldTicket->fail_reason);
    }

    public function test_payment_callback_success_redirect_omits_ticket_id(): void
    {
        Bus::fake([SendTicketEmailJob::class]);

        [$ticket, $soldTicket] = $this->createActiveTicketAndSoldTicket();

        $this->mockPaidPaymentGateway();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyQuipuHmac::class);

        config(['app.frontend_url' => 'http://localhost:3000']);

        $response = $this->get("/api/payments/callback?ref=ABCD1234&ID=999&sig=valid");
        $location = $response->headers->get('Location');

        $this->assertStringContainsString('/dashboard/success', $location);
        $this->assertStringNotContainsString('ticketId=', $location);
    }

    public function test_redirect_endpoint_with_valid_token(): void
    {
        $paymentService = app(PaymentService::class);
        $token = $paymentService->createRedirectToken(123, 'soldTickets');

        $response = $this->get("/api/payments/redirect?token={$token}");
        $response->assertRedirect();
    }

    public function test_redirect_endpoint_with_invalid_token(): void
    {
        $response = $this->get('/api/payments/redirect?token=invalid_garbage');
        $response->assertRedirect();
        $this->assertStringContainsString('fail', $response->headers->get('Location'));
    }
}
