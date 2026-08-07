<?php

namespace Tests\Feature;

use App\Filament\Pages\Accounting;
use App\Models\SoldTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_accounting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/accounting')
            ->assertOk();

        $this->actingAs($admin);
        $this->assertTrue(Accounting::canAccess());

        foreach (['editor', 'scanner', 'seller'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/admin/accounting')
                ->assertForbidden();

            $this->actingAs($user);
            $this->assertFalse(Accounting::canAccess(), "role {$role} should not access accounting");
        }
    }

    public function test_admin_can_download_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        SoldTicket::create([
            'id' => 'ACCTKT01',
            'personal_number' => '12345678901',
            'email' => 'online@test.com',
            'name' => 'Online',
            'surname' => 'Buyer',
            'amount' => 103,
            'base_amount' => 100,
            'surcharge_amount' => 3,
            'surcharge_rate' => 3,
            'status' => 'paid',
            'event_name' => 'Standard Night',
            'event_date' => '2026-08-05',
            'location' => 'Tbilisi',
            'paid_at' => '2026-08-05 12:00:00',
            'sold_by' => null,
            'is_joker' => false,
            'is_techno' => false,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(Accounting::class)
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-07')
            ->set('channel', 'all')
            ->call('exportCsv')
            ->assertFileDownloaded('accounting-2026-08-01-2026-08-07.csv');

        $csv = base64_decode(data_get($component->effects, 'download.content'));

        $this->assertStringContainsString('type,id,paid_at,channel,title', $csv);
        $this->assertStringContainsString('ACCTKT01', $csv);
        $this->assertStringContainsString('estimated_bank_fee', $csv);
    }
}
