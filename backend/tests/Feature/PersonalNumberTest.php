<?php

namespace Tests\Feature;

use App\Models\SoldTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_personal_number_returns_can_purchase_false_when_limit_reached(): void
    {
        for ($i = 0; $i < 2; $i++) {
            SoldTicket::create([
                'id' => 'PN' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
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

        SoldTicket::create([
            'id' => 'PN000002',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'event_name' => 'Test',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);

        $response = $this->postJson('/api/check-personal-number', [
            'personalNumber' => '12345678901',
        ]);

        $response->assertOk()
            ->assertJsonPath('canPurchase', false)
            ->assertJsonMissingPath('count');
    }

    public function test_check_personal_number_validates_input(): void
    {
        $response = $this->postJson('/api/check-personal-number', [
            'personalNumber' => '123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['personalNumber']);
    }

    public function test_check_personal_number_returns_can_purchase_true_for_new(): void
    {
        $response = $this->postJson('/api/check-personal-number', [
            'personalNumber' => '99999999999',
        ]);

        $response->assertOk()
            ->assertJsonPath('canPurchase', true);
    }

    public function test_pending_tickets_block_new_order(): void
    {
        $ticket = \App\Models\Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 50,
            'quantity' => 100,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        for ($i = 0; $i < 3; $i++) {
            SoldTicket::create([
                'id' => 'PND' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'personal_number' => '12345678901',
                'email' => 'test@test.com',
                'name' => 'John',
                'surname' => 'Doe',
                'amount' => 50,
                'status' => 'pending',
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
