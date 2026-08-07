<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WalkUpSaleColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sold_tickets_and_product_orders_have_sold_by_and_discount_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('sold_tickets', ['sold_by', 'discount_amount']));
        $this->assertTrue(Schema::hasColumns('product_orders', ['sold_by', 'discount_amount']));
    }
}
