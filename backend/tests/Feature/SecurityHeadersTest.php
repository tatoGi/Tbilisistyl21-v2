<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_scanner_allows_same_origin_camera(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/ticket-scanner');

        $response->assertOk();
        $this->assertStringContainsString(
            'camera=(self)',
            (string) $response->headers->get('Permissions-Policy'),
        );
        $this->assertStringContainsString(
            'microphone=()',
            (string) $response->headers->get('Permissions-Policy'),
        );
    }

    public function test_other_admin_pages_deny_camera(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $this->assertStringContainsString(
            'camera=()',
            (string) $response->headers->get('Permissions-Policy'),
        );
        $this->assertStringNotContainsString(
            'camera=(self)',
            (string) $response->headers->get('Permissions-Policy'),
        );
    }
}
