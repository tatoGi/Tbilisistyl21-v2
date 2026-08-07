<?php

namespace Tests\Unit;

use App\Models\ProductOrder;
use App\Models\SoldTicket;
use App\Services\AccountingReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $from;

    private Carbon $to;

    private AccountingReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->from = Carbon::parse('2026-08-01 00:00:00');
        $this->to = Carbon::parse('2026-08-07 23:59:59');
        $this->service = app(AccountingReportService::class);

        SoldTicket::create([
            'id' => 'ONLTKT01',
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

        SoldTicket::create([
            'id' => 'WLKTKT01',
            'personal_number' => '12345678902',
            'email' => 'walkup@test.com',
            'name' => 'Walk',
            'surname' => 'Up',
            'amount' => 80,
            'base_amount' => 80,
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
            'status' => 'paid',
            'event_name' => 'Joker Night',
            'event_date' => '2026-08-05',
            'location' => 'Tbilisi',
            'paid_at' => '2026-08-05 15:00:00',
            'sold_by' => 'Nino Seller',
            'is_joker' => true,
            'is_techno' => false,
        ]);

        ProductOrder::create([
            'id' => 'PROD0001',
            'product_id' => '11111111-1111-1111-1111-111111111111',
            'product_title' => 'T-Shirt',
            'size' => 'M',
            'name' => 'Prod',
            'surname' => 'Buyer',
            'email' => 'product@test.com',
            'phone' => '555000111',
            'amount' => 50,
            'base_amount' => 50,
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
            'status' => 'paid',
            'paid_at' => '2026-08-06 10:00:00',
            'sold_by' => 'Nino Seller',
        ]);
    }

    public function test_summary_for_all_and_online_channels(): void
    {
        $online = $this->service->summary($this->from, $this->to, 'online');

        $this->assertEquals(103.0, $online['gross']);
        $this->assertEquals(100.0, $online['base']);
        $this->assertEquals(3.0, $online['surcharge']);
        $this->assertEquals(2.58, $online['estimated_bank_fee']);
        $this->assertEquals(100.42, $online['estimated_net']);
        $this->assertEquals(1, $online['ticket_count']);
        $this->assertEquals(0, $online['product_count']);

        $all = $this->service->summary($this->from, $this->to, 'all');

        $this->assertEquals(233.0, $all['gross']);
        $this->assertEquals(230.0, $all['base']);
        $this->assertEquals(3.0, $all['surcharge']);
        $this->assertEquals(2.58, $all['estimated_bank_fee']);
        $this->assertEquals(230.42, $all['estimated_net']);
        $this->assertEquals(2, $all['ticket_count']);
        $this->assertEquals(1, $all['product_count']);

        $walkUp = $this->service->summary($this->from, $this->to, 'walk_up');

        $this->assertEquals(130.0, $walkUp['gross']);
        $this->assertEquals(0.0, $walkUp['estimated_bank_fee']);
        $this->assertEquals(130.0, $walkUp['estimated_net']);
        $this->assertEquals(1, $walkUp['ticket_count']);
        $this->assertEquals(1, $walkUp['product_count']);
    }

    public function test_breakdowns_and_csv_rows(): void
    {
        $byKind = $this->service->breakdownByKind($this->from, $this->to, 'all');
        $this->assertEquals(183.0, $byKind['tickets']['gross']);
        $this->assertEquals(2, $byKind['tickets']['count']);
        $this->assertEquals(50.0, $byKind['products']['gross']);
        $this->assertEquals(1, $byKind['products']['count']);

        $byType = $this->service->breakdownByTicketType($this->from, $this->to, 'all');
        $this->assertEquals(1, $byType['joker']['count']);
        $this->assertEquals(80.0, $byType['joker']['gross']);
        $this->assertEquals(0, $byType['techno']['count']);
        $this->assertEquals(1, $byType['standard']['count']);
        $this->assertEquals(103.0, $byType['standard']['gross']);

        $byDay = $this->service->breakdownByDay($this->from, $this->to, 'all');
        $this->assertEquals([
            ['date' => '2026-08-05', 'gross' => 183.0, 'count' => 2],
            ['date' => '2026-08-06', 'gross' => 50.0, 'count' => 1],
        ], $byDay);

        $rows = iterator_to_array($this->service->csvRows($this->from, $this->to, 'all'));
        $this->assertCount(3, $rows);
        $this->assertSame([
            'type', 'id', 'paid_at', 'channel', 'title', 'base_amount',
            'surcharge_rate', 'surcharge_amount', 'amount', 'estimated_bank_fee',
            'email', 'sold_by',
        ], array_keys($rows[0]));

        $onlineRow = collect($rows)->firstWhere('id', 'ONLTKT01');
        $this->assertSame('ticket', $onlineRow['type']);
        $this->assertSame('online', $onlineRow['channel']);
        $this->assertEquals(2.58, $onlineRow['estimated_bank_fee']);

        $walkRow = collect($rows)->firstWhere('id', 'WLKTKT01');
        $this->assertSame('walk_up', $walkRow['channel']);
        $this->assertEquals(0.0, $walkRow['estimated_bank_fee']);
    }
}
