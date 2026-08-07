<?php

namespace Tests\Feature;

use App\Actions\CreateWalkUpTicketSaleAction;
use App\Jobs\SendTicketEmailJob;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CreateWalkUpTicketSaleActionTest extends TestCase
{
    use RefreshDatabase;

    private function createTicket(int $quantity = 5): Ticket
    {
        return Ticket::create([
            'title' => ['ka' => 'ტესტ ბილეთი', 'en' => 'Test Ticket'],
            'description' => ['ka' => '', 'en' => ''],
            'price_gel' => 100,
            'quantity' => $quantity,
            'status' => 'active',
            'event_date' => '2026-08-20',
            'location' => 'Tbilisi',
        ]);
    }

    public function test_creates_paid_ticket_with_discount_and_seller_attribution(): void
    {
        Bus::fake();

        $ticket = $this->createTicket();

        $result = app(CreateWalkUpTicketSaleAction::class)->execute([
            'ticketId' => $ticket->id,
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'discountAmount' => 20,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(200, $result['status']);
        $soldTicket = $result['soldTicket'];
        $this->assertEquals('paid', $soldTicket->status);
        $this->assertEquals(80, (float) $soldTicket->amount);
        $this->assertEquals(20, (float) $soldTicket->discount_amount);
        $this->assertEquals('Nino Seller', $soldTicket->sold_by);

        $ticket->refresh();
        $this->assertEquals(4, $ticket->quantity);

        Bus::assertDispatched(SendTicketEmailJob::class);
    }

    public function test_returns_sold_out_when_ticket_has_no_quantity(): void
    {
        $ticket = $this->createTicket(quantity: 0);

        $result = app(CreateWalkUpTicketSaleAction::class)->execute([
            'ticketId' => $ticket->id,
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'discountAmount' => 0,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('sold_out', $result['error']);
    }
}
