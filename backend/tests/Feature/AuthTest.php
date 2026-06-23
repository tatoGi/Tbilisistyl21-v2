<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login(): void
    {
        User::factory()->create([
            'email' => 'admin@tbilisistyle.ge',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@tbilisistyle.ge',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'admin@tbilisistyle.ge',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@tbilisistyle.ge',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/admin/login', [
                'email' => 'admin@tbilisistyle.ge',
                'password' => 'wrong',
            ]);
        }

        $response->assertStatus(429);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/user');

        $response->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $this->getJson('/api/admin/user')->assertUnauthorized();
    }

    public function test_locale_middleware_sets_app_locale(): void
    {
        $response = $this->getJson('/api/locale', [
            'Accept-Language' => 'ka',
        ]);

        $response->assertOk()
            ->assertJsonPath('locale', 'ka');
    }

    public function test_locale_defaults_to_ka(): void
    {
        $response = $this->getJson('/api/locale');

        $response->assertOk()
            ->assertJsonPath('locale', 'ka');
    }
}
