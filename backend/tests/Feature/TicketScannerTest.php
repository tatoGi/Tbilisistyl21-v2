<?php

namespace Tests\Feature;

use App\Models\SoldTicket;
use App\Models\User;
use App\Services\QrCodeService;
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
            'original_ticket_id' => '11111111-1111-1111-1111-111111111111',
            'event_name' => 'Test Concert',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);
    }

    private function signedPayload(SoldTicket $ticket): array
    {
        $json = app(QrCodeService::class)->generateTicketData(
            $ticket->id,
            $ticket->personal_number,
            $ticket->original_ticket_id,
        );

        return json_decode($json, true);
    }

    public function test_validate_ticket_requires_auth(): void
    {
        $response = $this->postJson('/api/admin/validate-ticket', [
            'ticketId' => 'SCAN0001',
            'personalNumber' => '12345678901',
        ]);

        $response->assertUnauthorized();
    }

    public function test_validate_ticket_requires_admin_role(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $ticket = $this->createPaidTicket();

        $response = $this->actingAs($editor, 'sanctum')
            ->postJson('/api/admin/validate-ticket', $this->signedPayload($ticket));

        $response->assertForbidden();
    }

    public function test_validate_ticket_rejects_unsigned_qr(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPaidTicket();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', [
                'ticketId' => 'SCAN0001',
                'personalNumber' => '12345678901',
                'eventId' => '11111111-1111-1111-1111-111111111111',
                'version' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'invalid_qr_signature');
    }

    public function test_validate_ticket_succeeds_for_paid_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->createPaidTicket();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', $this->signedPayload($ticket));

        $response->assertOk()
            ->assertJsonPath('ticket.id', 'SCAN0001')
            ->assertJsonPath('ticket.name', 'John')
            ->assertJsonPath('ticket.surname', 'Doe');

        $ticket->refresh();
        $this->assertNotNull($ticket->scanned_at);
        $this->assertEquals('scanned', $ticket->status);
    }

    public function test_validate_ticket_rejects_already_scanned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->createPaidTicket();
        $payload = $this->signedPayload($ticket);

        $response1 = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', $payload);
        $response1->assertOk();

        $response2 = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', $payload);
        $response2->assertStatus(400)
            ->assertJsonPath('error', 'ticket_not_paid');
    }

    public function test_validate_ticket_rejects_wrong_personal_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->createPaidTicket();
        $payload = $this->signedPayload($ticket);
        $payload['personalNumber'] = '99999999999';
        $payload['sig'] = app(QrCodeService::class)->signPayload(
            $payload['ticketId'],
            $payload['personalNumber'],
            $payload['eventId'],
        );

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', $payload);

        $response->assertStatus(404)
            ->assertJsonPath('error', 'ticket_not_found');
    }

    public function test_validate_ticket_rejects_unpaid_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $ticket = SoldTicket::create([
            'id' => 'UNPD0001',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'Jane',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'original_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'event_name' => 'Test',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', $this->signedPayload($ticket));

        $response->assertStatus(400)
            ->assertJsonPath('error', 'ticket_not_paid');
    }

    public function test_validate_ticket_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $json = app(QrCodeService::class)->generateTicketData(
            'NONEXIST',
            '12345678901',
            '33333333-3333-3333-3333-333333333333',
        );

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/validate-ticket', json_decode($json, true));

        $response->assertStatus(404)
            ->assertJsonPath('error', 'ticket_not_found');
    }
}
