<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_editor_scanner_and_seller_can_access_panel(): void
    {
        $panel = Filament::getPanel('admin');

        foreach (['admin', 'editor', 'scanner', 'seller'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($user->canAccessPanel($panel), "role {$role} should access panel");
        }
    }

    public function test_is_scanner_and_is_seller_helpers(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner']);
        $seller = User::factory()->create(['role' => 'seller']);

        $this->assertTrue($scanner->isScanner());
        $this->assertFalse($scanner->isSeller());
        $this->assertTrue($seller->isSeller());
        $this->assertFalse($seller->isScanner());
    }
}
