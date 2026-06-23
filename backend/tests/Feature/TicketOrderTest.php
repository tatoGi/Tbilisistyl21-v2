<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ticket_order_validates_input(): void
    {
        $response = $this->postJson('/api/orders/tickets', []);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ticketId', 'name', 'surname', 'email', 'personalNumber']);
    }

    public function test_create_ticket_order_rejects_sold_out(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 50,
            'quantity' => 0,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/orders/tickets', [
            'ticketId' => $ticket->id,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'personalNumber' => '12345678901',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'sold_out');
    }

    public function test_personal_number_max_3_tickets(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 50,
            'quantity' => 100,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        // Create 3 paid tickets for same personal number
        for ($i = 0; $i < 3; $i++) {
            \App\Models\SoldTicket::create([
                'id' => 'TST' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'personal_number' => '12345678901',
                'email' => 'test@test.com',
                'name' => 'John',
                'surname' => 'Doe',
                'amount' => 50,
                'status' => 'paid',
                'event_name' => 'Test',
                'event_date' => '2026-08-01',
                'location' => 'Tbilisi',
            ]);
        }

        $response = $this->postJson('/api/orders/tickets', [
            'ticketId' => $ticket->id,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'personalNumber' => '12345678901',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'max_tickets_reached');
    }
}
