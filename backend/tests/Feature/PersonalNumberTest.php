<?php

namespace Tests\Feature;

use App\Models\SoldTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_personal_number_returns_count(): void
    {
        // Create 2 paid tickets for this personal number
        for ($i = 0; $i < 2; $i++) {
            SoldTicket::create([
                'id' => 'PN' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'personal_number' => '12345678901',
                'email' => 'test@test.com',
                'name' => 'John',
                'surname' => 'Doe',
                'amount' => 50,
                'status' => 'paid',
                'event_name' => 'Test',
                'event_date' => '2026-08-01',
                'location' => 'Tbilisi',
            ]);
        }

        // One pending (should not count)
        SoldTicket::create([
            'id' => 'PN000002',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'event_name' => 'Test',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);

        $response = $this->postJson('/api/check-personal-number', [
            'personalNumber' => '12345678901',
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('remaining', 1);
    }

    public function test_check_personal_number_validates_input(): void
    {
        $response = $this->postJson('/api/check-personal-number', [
            'personalNumber' => '123', // too short
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['personalNumber']);
    }

    public function test_check_personal_number_returns_zero_for_new(): void
    {
        $response = $this->postJson('/api/check-personal-number', [
            'personalNumber' => '99999999999',
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('remaining', 3);
    }
}
