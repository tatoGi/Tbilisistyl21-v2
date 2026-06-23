<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\SoldTicket;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductOrder;
use App\Models\MusicTrack;
use App\Models\Page;
use App\Models\Post;
use App\Models\Partner;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->assertEquals('admin', $user->role);
        $this->assertTrue($user->isAdmin());
    }

    public function test_ticket_is_translatable(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'ბილეთი', 'en' => 'Ticket'],
            'description' => ['ka' => 'აღწერა', 'en' => 'Description'],
            'price_gel' => 50,
            'quantity' => 100,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);
        $this->assertEquals('ბილეთი', $ticket->setLocale('ka')->title);
        $this->assertEquals('Ticket', $ticket->setLocale('en')->title);
    }

    public function test_ticket_active_scope(): void
    {
        Ticket::create([
            'title' => ['ka' => 'Active'],
            'price_gel' => 10,
            'quantity' => 5,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);
        Ticket::create([
            'title' => ['ka' => 'Draft'],
            'price_gel' => 10,
            'quantity' => 5,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'draft',
        ]);
        $this->assertCount(1, Ticket::active()->get());
    }

    public function test_product_has_sizes(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური'],
            'price_gel' => 30,
            'status' => 'active',
        ]);
        $product->sizes()->create(['size' => 'M', 'quantity' => 10]);
        $product->sizes()->create(['size' => 'L', 'quantity' => 5]);
        $this->assertCount(2, $product->sizes);
        $this->assertEquals(15, $product->sizes->sum('quantity'));
    }

    public function test_sold_ticket_belongs_to_no_user(): void
    {
        // SoldTickets are created by payment flow, not user-owned
        $sold = SoldTicket::create([
            'id' => 'ABCD1234',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'event_name' => 'Festival',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);
        $this->assertEquals('pending', $sold->status);
    }

    public function test_music_track_ordered_scope(): void
    {
        MusicTrack::create(['title' => ['ka' => 'B'], 'artist' => 'X', 'order' => 2, 'status' => 'active']);
        MusicTrack::create(['title' => ['ka' => 'A'], 'artist' => 'Y', 'order' => 1, 'status' => 'active']);
        $tracks = MusicTrack::ordered()->get();
        $this->assertEquals('A', $tracks->first()->setLocale('ka')->title);
    }

    public function test_page_published_scope(): void
    {
        Page::create(['title' => ['ka' => 'Published'], 'slug' => 'pub', 'is_published' => true]);
        Page::create(['title' => ['ka' => 'Draft'], 'slug' => 'draft', 'is_published' => false]);
        $this->assertCount(1, Page::published()->get());
    }
}
