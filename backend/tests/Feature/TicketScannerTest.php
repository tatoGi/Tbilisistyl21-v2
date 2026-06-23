<?php

namespace Tests\Feature;

use App\Models\SoldTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketScannerTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidTicket(): SoldTicket
    {
        return SoldTicket::create([
            'id' => 'SCAN0001',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'paid',
            'paid_at' => now(),
            'event_name' => 'Test Concert',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);
    }

    public function test_validate_ticket_requires_auth(): void
    {
        $response = $this->postJson('/api/admin/validate-ticket', [
            'ticketId' => 'SCAN0001',
            'personalNumber' => '12345678901',
        ]);

        $response->assertUnauthorized();
    }

    public function test_validate_ticket_succeeds_for_paid_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPaidTicket();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'SCAN0001',
                'personalNumber' => '12345678901',
            ]);

        $response->assertOk()
            ->assertJsonPath('ticket.id', 'SCAN0001')
            ->assertJsonPath('ticket.name', 'John')
            ->assertJsonPath('ticket.surname', 'Doe');

        // Verify scanned_at set
        $ticket = SoldTicket::find('SCAN0001');
        $this->assertNotNull($ticket->scanned_at);
        $this->assertEquals('scanned', $ticket->status);
    }

    public function test_validate_ticket_rejects_already_scanned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPaidTicket();

        // First scan succeeds
        $response1 = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'SCAN0001',
                'personalNumber' => '12345678901',
            ]);
        $response1->assertOk();

        // Second scan on same ticket is rejected (status is now 'scanned', not 'paid')
        $response2 = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'SCAN0001',
                'personalNumber' => '12345678901',
            ]);
        $response2->assertStatus(400)
            ->assertJsonPath('error', 'ticket_not_paid');
    }

    public function test_validate_ticket_rejects_wrong_personal_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPaidTicket();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'SCAN0001',
                'personalNumber' => '99999999999',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'personal_number_mismatch');
    }

    public function test_validate_ticket_rejects_unpaid_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        SoldTicket::create([
            'id' => 'UNPD0001',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'Jane',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'event_name' => 'Test',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'UNPD0001',
                'personalNumber' => '12345678901',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'ticket_not_paid');
    }

    public function test_validate_ticket_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'NONEXIST',
                'personalNumber' => '12345678901',
            ]);

        $response->assertStatus(404)
            ->assertJsonPath('error', 'ticket_not_found');
    }
}
