<?php

namespace Tests\Feature;

use App\Models\MusicTrack;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_active_tickets(): void
    {
        Ticket::create([
            'title' => ['ka' => 'Active', 'en' => 'Active'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'active',
        ]);
        Ticket::create([
            'title' => ['ka' => 'Draft'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'draft',
        ]);

        $response = $this->getJson('/api/tickets');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_get_single_ticket(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Test', 'en' => 'Test EN'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'active',
        ]);

        $response = $this->getJson("/api/tickets/{$ticket->id}", ['Accept-Language' => 'en']);
        $response->assertOk()->assertJsonPath('data.title.en', 'Test EN');
    }

    public function test_get_draft_ticket_returns_404(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Draft'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'draft',
        ]);

        $this->getJson("/api/tickets/{$ticket->id}")->assertNotFound();
    }

    public function test_get_draft_product_returns_404(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'Draft shirt'],
            'price_gel' => 30,
            'status' => 'draft',
        ]);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
    }

    public function test_list_products_with_sizes(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'Shirt'], 'price_gel' => 30, 'status' => 'active',
        ]);
        $product->sizes()->create(['size' => 'M', 'quantity' => 5]);

        $response = $this->getJson('/api/products');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sizes.0.size', 'M');
    }

    public function test_get_page_by_slug(): void
    {
        Page::create([
            'title' => ['ka' => 'About'], 'slug' => 'about', 'is_published' => true,
        ]);

        $response = $this->getJson('/api/pages/about');
        $response->assertOk()->assertJsonPath('data.slug', 'about');
    }

    public function test_get_page_by_route(): void
    {
        Page::create([
            'title' => ['ka' => 'Main'],
            'slug' => 'main-stage',
            'route_path' => '/dashboard/mainStage',
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/pages/by-route?path='.urlencode('/dashboard/mainStage'));
        $response->assertOk()->assertJsonPath('data.route_path', '/dashboard/mainStage');
    }

    public function test_get_unpublished_page_returns_404(): void
    {
        Page::create([
            'title' => ['ka' => 'Draft'], 'slug' => 'draft', 'is_published' => false,
        ]);

        $this->getJson('/api/pages/draft')->assertNotFound();
    }

    public function test_list_music_tracks_ordered(): void
    {
        MusicTrack::create(['title' => ['ka' => 'B'], 'artist' => 'X', 'order' => 2, 'status' => 'active']);
        MusicTrack::create(['title' => ['ka' => 'A'], 'artist' => 'Y', 'order' => 1, 'status' => 'active']);

        $response = $this->getJson('/api/music-tracks');
        $response->assertOk()->assertJsonPath('data.0.title.ka', 'A');
    }

    public function test_site_settings(): void
    {
        SiteSetting::set('heroTitle', ['ka' => 'გამარჯობა', 'en' => 'Hello']);

        $response = $this->getJson('/api/site-settings');
        $response->assertOk()->assertJsonPath('data.heroTitle.ka', 'გამარჯობა');
    }
}
