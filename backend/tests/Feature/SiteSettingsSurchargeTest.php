<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PaymentSurchargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsSurchargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_payment_surcharge_percent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(SiteSettings::class)
            ->fillForm(['payment_surcharge_percent' => 3.5])
            ->call('save');

        $this->assertEquals(3.5, app(PaymentSurchargeService::class)->rate());
        $this->assertEquals(
            ['percent' => 3.5],
            SiteSetting::get('payment_surcharge_percent')
        );
    }
}
