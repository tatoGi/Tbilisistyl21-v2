<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPayablePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_index_exposes_payable_price_gel(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);

        Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 100,
            'quantity' => 10,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonPath('data.0.price_gel', 103);
    }

    public function test_products_index_exposes_payable_price_gel(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);

        Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-Shirt'],
            'price_gel' => 100,
            'status' => 'active',
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.price_gel', 103);
    }

    public function test_ticket_show_exposes_payable_price_gel_without_mutating_db(): void
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

        $this->getJson('/api/tickets/'.$ticket->id)
            ->assertOk()
            ->assertJsonPath('data.price_gel', 103);

        $this->assertEquals(100, (float) $ticket->fresh()->price_gel);
    }

    public function test_product_show_exposes_payable_price_gel_without_mutating_db(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);

        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-Shirt'],
            'price_gel' => 100,
            'status' => 'active',
        ]);

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.price_gel', 103);

        $this->assertEquals(100, (float) $product->fresh()->price_gel);
    }
}
