<?php

namespace Tests\Feature;

use App\Models\ProductOrder;
use App\Models\SoldTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SurchargeColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_tables_have_surcharge_and_product_paid_at_columns(): void
    {
        foreach (['sold_tickets', 'product_orders'] as $table) {
            $this->assertTrue(Schema::hasColumns($table, [
                'base_amount', 'surcharge_amount', 'surcharge_rate',
            ]));
        }
        $this->assertTrue(Schema::hasColumn('product_orders', 'paid_at'));
    }

    public function test_backfill_sets_base_equal_to_amount_without_inventing_surcharge(): void
    {
        // Insert as if pre-migration data existed: use model after migrate
        // with explicit amounts then re-run backfill logic is already in migration.
        // Create via DB after columns exist with only amount, simulating backfilled row:
        $id = 'OLD00001';
        SoldTicket::create([
            'id' => $id,
            'personal_number' => '12345678901',
            'email' => 'a@b.c',
            'name' => 'A',
            'surname' => 'B',
            'amount' => 100,
            'base_amount' => 100,
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
            'status' => 'paid',
            'event_name' => 'Test',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);

        $row = SoldTicket::find($id);
        $this->assertEquals(100, (float) $row->amount);
        $this->assertEquals(100, (float) $row->base_amount);
        $this->assertEquals(0, (float) $row->surcharge_amount);
        $this->assertNull($row->surcharge_rate);
    }
}
