<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_cannot_access_pages_or_site_settings(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner']);

        $this->actingAs($scanner)
            ->get('/admin/pages')
            ->assertForbidden();

        $this->actingAs($scanner)
            ->get('/admin/site-settings')
            ->assertForbidden();
    }

    public function test_seller_cannot_access_pages_or_site_settings(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->get('/admin/pages')
            ->assertForbidden();

        $this->actingAs($seller)
            ->get('/admin/site-settings')
            ->assertForbidden();
    }

    public function test_editor_can_access_pages_and_site_settings(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->get('/admin/pages')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/admin/site-settings')
            ->assertOk();
    }
}
