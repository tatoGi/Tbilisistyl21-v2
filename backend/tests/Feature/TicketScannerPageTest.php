<?php

namespace Tests\Feature;

use App\Filament\Pages\TicketScanner;
use App\Models\SoldTicket;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketScannerPageTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidTicket(): SoldTicket
    {
        return SoldTicket::create([
            'id' => 'PAGE0001',
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

    public function test_scanner_role_can_access_page(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner']);

        $this->actingAs($scanner)
            ->get('/admin/ticket-scanner')
            ->assertOk();
    }

    public function test_editor_role_cannot_access_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->get('/admin/ticket-scanner')
            ->assertForbidden();
    }

    public function test_scan_records_authenticated_users_name(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner', 'name' => 'Nino Beridze']);
        $ticket = $this->createPaidTicket();

        $json = app(QrCodeService::class)->generateTicketData(
            $ticket->id,
            $ticket->personal_number,
            $ticket->original_ticket_id,
        );

        Livewire::actingAs($scanner)
            ->test(TicketScanner::class)
            ->call('scan', $json)
            ->assertSet('success', true);

        $ticket->refresh();
        $this->assertEquals('Nino Beridze', $ticket->scanned_by);
    }
}
