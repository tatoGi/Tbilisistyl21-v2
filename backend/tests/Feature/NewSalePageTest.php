<?php

namespace Tests\Feature;

use App\Filament\Pages\NewSale;
use App\Jobs\SendTicketEmailJob;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class NewSalePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_role_cannot_access_new_sale_page(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner']);

        $this->actingAs($scanner)
            ->get('/admin/new-sale')
            ->assertForbidden();
    }

    public function test_seller_can_access_new_sale_page(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->get('/admin/new-sale')
            ->assertOk();
    }

    public function test_seller_can_record_a_ticket_sale(): void
    {
        Bus::fake();

        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Nino Seller']);

        $ticket = Ticket::create([
            'title' => ['ka' => 'ტესტ ბილეთი', 'en' => 'Test Ticket'],
            'description' => ['ka' => '', 'en' => ''],
            'price_gel' => 100,
            'quantity' => 5,
            'status' => 'active',
            'event_date' => '2026-08-20',
            'location' => 'Tbilisi',
        ]);

        Livewire::actingAs($seller)
            ->test(NewSale::class)
            ->fillForm([
                'type' => 'ticket',
                'ticketId' => $ticket->id,
                'name' => 'Giorgi',
                'surname' => 'Beridze',
                'personalNumber' => '01011011011',
                'email' => 'giorgi@example.com',
                'discountAmount' => 10,
            ])
            ->call('create');

        $this->assertDatabaseHas('sold_tickets', [
            'personal_number' => '01011011011',
            'status' => 'paid',
            'sold_by' => 'Nino Seller',
            'amount' => 90,
        ]);

        Bus::assertDispatched(SendTicketEmailJob::class);
    }
}
