<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards against the Filament `getStateUsing($state)` infinite-recursion that
 * made /admin/tickets exhaust memory (HTTP 500). Rendering the list with at
 * least one ticket must succeed.
 */
class TicketsAdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_admin_list_loads(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'secret']);
        $admin->forceFill(['role' => 'admin'])->save();

        Ticket::create([
            'title' => ['ka' => 'ტესტი', 'en' => 'Test'],
            'description' => ['ka' => '', 'en' => ''],
            'image' => '/storage/media/example.png',
            'price_gel' => 10,
            'quantity' => 5,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/tickets')
            ->assertSuccessful();
    }
}
