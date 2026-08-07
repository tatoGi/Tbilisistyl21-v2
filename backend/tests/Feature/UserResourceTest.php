<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_view_user_list(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Test Seller',
                'email' => 'seller1@tbilisistyle.ge',
                'password' => 'password123',
                'role' => 'seller',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'seller1@tbilisistyle.ge')->firstOrFail();
        $this->assertEquals('seller', $created->role);
    }

    public function test_generate_scanner_accounts_creates_numbered_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction('generateScanners', data: ['count' => 3]);

        $this->assertEquals(3, User::where('role', 'scanner')->count());
        $this->assertDatabaseHas('users', ['email' => 'scanner01@tbilisistyle.ge']);
        $this->assertDatabaseHas('users', ['email' => 'scanner02@tbilisistyle.ge']);
        $this->assertDatabaseHas('users', ['email' => 'scanner03@tbilisistyle.ge']);
    }

    public function test_generate_scanner_accounts_continues_numbering(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'scanner', 'email' => 'scanner01@tbilisistyle.ge']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction('generateScanners', data: ['count' => 2]);

        $this->assertDatabaseHas('users', ['email' => 'scanner02@tbilisistyle.ge']);
        $this->assertDatabaseHas('users', ['email' => 'scanner03@tbilisistyle.ge']);
    }
}
