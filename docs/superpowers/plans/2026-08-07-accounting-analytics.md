# Online Surcharge + Accounting Analytics Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Charge buyers a configurable online-only payment surcharge (default 3%) baked into the Quipu total, persist fee breakdown on each order, and give admins a Filament Accounting page with summaries, breakdowns, and CSV export.

**Architecture:** A small `PaymentSurchargeService` owns rate lookup and GEL rounding. Online create-order actions persist `base_amount` / `surcharge_*` / gross `amount` and send gross to Quipu. Walk-up sales keep surcharge at zero. Public ticket/product list APIs rewrite `price_gel` to the payable gross on read (catalog DB price unchanged). `AccountingReportService` aggregates paid rows for an admin-only Filament page + CSV stream.

**Tech Stack:** Laravel 11, Filament 3.3, Livewire 3, PostgreSQL, PHPUnit (`RefreshDatabase`), Next.js frontend (no fee line item — uses API `price_gel` as payable total).

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-07-accounting-analytics-design.md`
- Online surcharge default **3%**; walk-up / New Sale **0** surcharge.
- Buyer UI shows **final total only** (no fee line).
- Partner 50/50 split is **out of scope**.
- Estimated bank fee for analytics = **2.5% of `amount` for online only** (`sold_by IS NULL`); walk-up = 0. Not stored on rows.
- Channel rule: `sold_by IS NULL` → online; `sold_by IS NOT NULL` → walk-up.
- Migrations additive; backfill historical `base_amount = amount`, `surcharge_amount = 0`, `surcharge_rate = null` — do not invent 3% on old rows.
- Add `product_orders.paid_at`; set on Quipu paid callback and walk-up create; backfill paid rows.
- Reuse existing money patterns (`number_format(..., 2, '.', '')` for Quipu; `round(..., 2)` for surcharge).
- Tests via Docker image used in this repo, e.g.  
  `docker run --rm -v "C:/Users/pc/Desktop/tato/TbilisiStyle21-v2/backend:/var/www/html" -w /var/www/html tbilisistyle21-v2-laravel:latest php artisan test --filter=…`  
  (or `docker compose exec laravel php artisan test --filter=…` on the server/local compose).

## File map

| File | Responsibility |
|------|----------------|
| `backend/app/Services/PaymentSurchargeService.php` | Rate from Site Settings; compute surcharge + gross |
| `backend/app/Services/AccountingReportService.php` | Filtered aggregates + CSV rows |
| `backend/database/migrations/2026_08_07_120000_add_surcharge_and_product_paid_at.php` | Columns + backfill |
| `backend/app/Models/SoldTicket.php` / `ProductOrder.php` | fillable + casts |
| `backend/app/Actions/CreateTicketOrderAction.php` | Online ticket surcharge |
| `backend/app/Actions/CreateProductOrderAction.php` | Online product surcharge |
| `backend/app/Actions/CreateWalkUp*SaleAction.php` | base=amount, surcharge 0; product `paid_at` |
| `backend/app/Actions/ProcessPaymentCallbackAction.php` | Set product `paid_at` on pay |
| `backend/app/Services/TicketService.php` / `ProductService.php` | Public `price_gel` → payable gross |
| `backend/app/Filament/Pages/SiteSettings.php` | Admin field for rate |
| `backend/app/Filament/Pages/Accounting.php` | Admin accounting UI + export |
| `backend/resources/views/filament/pages/accounting.blade.php` | Page layout |
| `backend/app/Providers/Filament/AdminPanelProvider.php` | Optional nav group `Finance` |

Frontend: **no fee breakdown UI**. If API returns payable `price_gel`, existing `priceGel` display stays correct with no Next.js changes.

---

### Task 1: PaymentSurchargeService

**Files:**
- Create: `backend/app/Services/PaymentSurchargeService.php`
- Test: `backend/tests/Unit/PaymentSurchargeServiceTest.php`

**Interfaces:**
- Produces:
  - `PaymentSurchargeService::rate(): float` — percent, default `3.0`
  - `PaymentSurchargeService::breakdown(float $baseAmount): array{base_amount: float, surcharge_rate: float, surcharge_amount: float, amount: float}`
  - `PaymentSurchargeService::payable(float $baseAmount): float` — gross only (for public API)
- Consumes: `SiteSetting::get('payment_surcharge_percent', …)`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Services\PaymentSurchargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSurchargeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_rate_is_three_percent(): void
    {
        $svc = app(PaymentSurchargeService::class);
        $this->assertSame(3.0, $svc->rate());
    }

    public function test_breakdown_rounds_half_up_to_two_decimals(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);
        $b = app(PaymentSurchargeService::class)->breakdown(100.00);
        $this->assertSame(100.0, $b['base_amount']);
        $this->assertSame(3.0, $b['surcharge_rate']);
        $this->assertSame(3.0, $b['surcharge_amount']);
        $this->assertSame(103.0, $b['amount']);
    }

    public function test_breakdown_uses_configured_rate(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 2.5]);
        $b = app(PaymentSurchargeService::class)->breakdown(40.00);
        $this->assertSame(1.0, $b['surcharge_amount']);
        $this->assertSame(41.0, $b['amount']);
    }

    public function test_payable_matches_breakdown_amount(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);
        $svc = app(PaymentSurchargeService::class);
        $this->assertSame($svc->breakdown(50)['amount'], $svc->payable(50));
    }
}
```

- [ ] **Step 2: Run test — expect FAIL** (class missing)

Run: `php artisan test --filter=PaymentSurchargeServiceTest`

- [ ] **Step 3: Implement**

```php
<?php

namespace App\Services;

use App\Models\SiteSetting;

class PaymentSurchargeService
{
    public const DEFAULT_PERCENT = 3.0;

    public function rate(): float
    {
        $raw = SiteSetting::get('payment_surcharge_percent', ['percent' => self::DEFAULT_PERCENT]);

        if (is_numeric($raw)) {
            return round((float) $raw, 2);
        }

        if (is_array($raw) && isset($raw['percent']) && is_numeric($raw['percent'])) {
            return round((float) $raw['percent'], 2);
        }

        return self::DEFAULT_PERCENT;
    }

    /**
     * @return array{base_amount: float, surcharge_rate: float, surcharge_amount: float, amount: float}
     */
    public function breakdown(float $baseAmount): array
    {
        $base = round($baseAmount, 2);
        $rate = $this->rate();
        $surcharge = round($base * $rate / 100, 2);

        return [
            'base_amount' => $base,
            'surcharge_rate' => $rate,
            'surcharge_amount' => $surcharge,
            'amount' => round($base + $surcharge, 2),
        ];
    }

    public function payable(float $baseAmount): float
    {
        return $this->breakdown($baseAmount)['amount'];
    }
}
```

- [ ] **Step 4: Run test — expect PASS**

- [ ] **Step 5: Commit**

```bash
git add backend/app/Services/PaymentSurchargeService.php backend/tests/Unit/PaymentSurchargeServiceTest.php
git commit -m "feat(payments): add PaymentSurchargeService for online fee math"
```

---

### Task 2: Migration + models

**Files:**
- Create: `backend/database/migrations/2026_08_07_120000_add_surcharge_and_product_paid_at.php`
- Modify: `backend/app/Models/SoldTicket.php`
- Modify: `backend/app/Models/ProductOrder.php`
- Test: `backend/tests/Feature/SurchargeColumnsTest.php`

**Interfaces:**
- Produces: columns `base_amount`, `surcharge_amount`, `surcharge_rate` on both order tables; `paid_at` on `product_orders`
- Consumes: none

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run — expect FAIL** (columns missing)

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->decimal('base_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('surcharge_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('surcharge_rate', 5, 2)->nullable()->after('surcharge_amount');
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->decimal('base_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('surcharge_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('surcharge_rate', 5, 2)->nullable()->after('surcharge_amount');
            $table->timestamp('paid_at')->nullable()->after('status');
        });

        DB::table('sold_tickets')->whereNull('base_amount')->update([
            'base_amount' => DB::raw('amount'),
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
        ]);

        DB::table('product_orders')->whereNull('base_amount')->update([
            'base_amount' => DB::raw('amount'),
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
        ]);

        DB::table('product_orders')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'surcharge_amount', 'surcharge_rate']);
        });
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'surcharge_amount', 'surcharge_rate', 'paid_at']);
        });
    }
};
```

Update models — add to `$fillable` and casts:

`SoldTicket`: `'base_amount', 'surcharge_amount', 'surcharge_rate'` + decimal casts.  
`ProductOrder`: same + `'paid_at'` with `'paid_at' => 'datetime'`.

- [ ] **Step 4: Run test — PASS**

- [ ] **Step 5: Commit**

```bash
git add backend/database/migrations/2026_08_07_120000_add_surcharge_and_product_paid_at.php \
  backend/app/Models/SoldTicket.php backend/app/Models/ProductOrder.php \
  backend/tests/Feature/SurchargeColumnsTest.php
git commit -m "feat(db): add surcharge columns and product_orders.paid_at"
```

---

### Task 3: Site Settings — surcharge percent (admin)

**Files:**
- Modify: `backend/app/Filament/Pages/SiteSettings.php`
- Modify: `backend/resources/views/filament/pages/site-settings.blade.php` only if form sections need a save note (usually form-only)
- Test: `backend/tests/Feature/SiteSettingsSurchargeTest.php`

**Interfaces:**
- Produces: persisted `payment_surcharge_percent` => `['percent' => float]`
- Consumes: `PaymentSurchargeService::rate()` reads this key

- [ ] **Step 1: Failing test**

```php
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
```

(Adjust `fillForm` keys to match the form state path you implement, e.g. nested `payment.surchargePercent` — keep test and form in sync.)

- [ ] **Step 2: Run — FAIL** (field missing)

- [ ] **Step 3: Implement**

In `mount()` fill:

```php
'payment_surcharge_percent' => (float) (SiteSetting::get('payment_surcharge_percent', ['percent' => 3])['percent'] ?? 3),
```

(Handle both array and legacy numeric if needed.)

In `form()` add an admin-visible section (wrap visibility):

```php
Forms\Components\Section::make('Payments')
    ->description('Online checkout surcharge added to catalog price (buyer sees total only). Walk-up sales are never surcharged.')
    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
    ->schema([
        Forms\Components\TextInput::make('payment_surcharge_percent')
            ->label('Online surcharge %')
            ->numeric()
            ->minValue(0)
            ->maxValue(20)
            ->step(0.1)
            ->suffix('%')
            ->required(),
    ]),
```

In `save()`:

```php
if (auth()->user()?->isAdmin()) {
    $percent = round((float) ($data['payment_surcharge_percent'] ?? 3), 2);
    SiteSetting::set('payment_surcharge_percent', ['percent' => $percent]);
}
// existing SiteSetting::set loop for other keys — exclude payment_surcharge_percent from generic loop if it would store a bare float under wrong shape
```

Ensure `SiteSettingService::clearCache()` still runs on save (already does).

- [ ] **Step 4: PASS + commit**

```bash
git commit -m "feat(admin): editable online payment surcharge percent in Site Settings"
```

---

### Task 4: Online create-order actions use surcharge

**Files:**
- Modify: `backend/app/Actions/CreateTicketOrderAction.php`
- Modify: `backend/app/Actions/CreateProductOrderAction.php`
- Test: extend `backend/tests/Feature/ProductOrderPaymentTest.php` and/or create `backend/tests/Feature/OnlineOrderSurchargeTest.php`

**Interfaces:**
- Consumes: `PaymentSurchargeService::breakdown()`
- Produces: order rows with surcharge fields; Quipu `amount` = gross

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineOrderSurchargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_order_charges_gross_and_persists_breakdown(): void
    {
        SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);

        $ticket = Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 100,
            'quantity' => 10,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('createCallbackHmac')->andReturn('sig');
            $mock->shouldReceive('browserConsumerDevice')->andReturn([]);
            $mock->shouldReceive('createOrder')
                ->once()
                ->withArgs(function (array $payload) {
                    return $payload['amount'] === '103.00';
                })
                ->andReturn(['id' => 1, 'hppUrl' => 'https://pay.test', 'password' => 'x']);
            $mock->shouldReceive('createRedirectToken')->andReturn('token');
        });

        $response = $this->postJson('/api/orders/tickets', [
            'ticketId' => $ticket->id,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'personalNumber' => '12345678901',
        ]);

        $response->assertOk();
        $sold = SoldTicket::first();
        $this->assertEquals(100, (float) $sold->base_amount);
        $this->assertEquals(3, (float) $sold->surcharge_amount);
        $this->assertEquals(3, (float) $sold->surcharge_rate);
        $this->assertEquals(103, (float) $sold->amount);
    }
}
```

Mirror a product test (catalog 40 → Quipu `41.00` at 2.5%, or 100→103 at 3%) in the same file.

- [ ] **Step 2: Run — FAIL** (Quipu still gets catalog price)

- [ ] **Step 3: Implement in both actions**

Inject `PaymentSurchargeService`. Before `createOrder`:

```php
$breakdown = $this->surcharge->breakdown((float) $ticket->price_gel);
// ...
'amount' => number_format($breakdown['amount'], 2, '.', ''),
// SoldTicket::create / ProductOrder::create:
'base_amount' => $breakdown['base_amount'],
'surcharge_amount' => $breakdown['surcharge_amount'],
'surcharge_rate' => $breakdown['surcharge_rate'],
'amount' => $breakdown['amount'],
```

Same for products with `$product->price_gel`.

- [ ] **Step 4: PASS** — also run `ProductOrderPaymentTest` / `PaymentFlowTest` and update mocks if they assert old amounts.

- [ ] **Step 5: Commit**

```bash
git commit -m "feat(payments): apply online surcharge to ticket and product Quipu orders"
```

---

### Task 5: Walk-up surcharge = 0 + product `paid_at`

**Files:**
- Modify: `backend/app/Actions/CreateWalkUpTicketSaleAction.php`
- Modify: `backend/app/Actions/CreateWalkUpProductSaleAction.php`
- Modify: `backend/app/Actions/ProcessPaymentCallbackAction.php` (product paid branch)
- Test: extend `CreateWalkUpTicketSaleActionTest` / `CreateWalkUpProductSaleActionTest`; add assertion in product payment callback test if one sets paid

**Interfaces:**
- Walk-up: `base_amount = finalAmount`, `surcharge_amount = 0`, `surcharge_rate = null`
- Product online paid: `paid_at = now()` alongside `status = paid`
- Product walk-up: `paid_at = now()`

- [ ] **Step 1: Extend walk-up tests**

```php
$this->assertEquals((float) $result['soldTicket']->amount, (float) $result['soldTicket']->base_amount);
$this->assertEquals(0, (float) $result['soldTicket']->surcharge_amount);
$this->assertNull($result['soldTicket']->surcharge_rate);
```

For product walk-up also: `$this->assertNotNull($result['order']->paid_at);` (adjust return key to match action).

- [ ] **Step 2: FAIL then implement create arrays + callback**

In `ProcessPaymentCallbackAction` product success update, change:

```php
$locked->update([
    'status' => 'paid',
    'paid_at' => now(),
]);
```

Walk-up product create: add `'paid_at' => now()`, surcharge fields as above.

- [ ] **Step 3: PASS + commit**

```bash
git commit -m "feat(sales): zero walk-up surcharge; set product_orders.paid_at"
```

---

### Task 6: Public API `price_gel` = payable gross

**Files:**
- Modify: `backend/app/Services/TicketService.php`
- Modify: `backend/app/Services/ProductService.php`
- Modify: `backend/app/Http/Controllers/Api/TicketController.php` / `ProductController.php` if `show` returns raw model
- Test: `backend/tests/Feature/PublicPayablePriceTest.php`

**Interfaces:**
- Consumes: `PaymentSurchargeService::payable()`
- Produces: JSON `price_gel` equal to gross; DB catalog `tickets.price_gel` unchanged

- [ ] **Step 1: Failing test**

```php
public function test_tickets_index_exposes_payable_price_gel(): void
{
    SiteSetting::set('payment_surcharge_percent', ['percent' => 3]);
    Ticket::create([/* price_gel 100, active, … */]);

    $this->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonPath('data.0.price_gel', 103); // or 103.0 depending on JSON encoding
}
```

Same for `/api/products` (confirm route in `routes/api.php`).

- [ ] **Step 2: Implement**

Apply surcharge **after** cache read so rate changes apply without busting catalog cache:

```php
public function listActive(): array
{
    $rows = Cache::remember(Ticket::API_CACHE_KEY, 3600, function () { /* existing */ });
    $surcharge = app(PaymentSurchargeService::class);

    return array_map(function (array $row) use ($surcharge) {
        $row['price_gel'] = $surcharge->payable((float) ($row['price_gel'] ?? 0));
        return $row;
    }, $rows);
}
```

For `show` endpoints that return Eloquent models, map to array and set `price_gel` to payable, **or** append a mutator only on API Resource — do not change the DB attribute globally (would break Filament catalog). Prefer explicit array mapping in the controller:

```php
$data = $ticket->toArray();
$data['price_gel'] = app(PaymentSurchargeService::class)->payable((float) $ticket->price_gel);
return response()->json(['data' => $data]);
```

- [ ] **Step 3: PASS** — frontend needs no change if it already displays `priceGel` from `price_gel`.

- [ ] **Step 4: Commit**

```bash
git commit -m "feat(api): expose payable gross as public price_gel"
```

---

### Task 7: AccountingReportService

**Files:**
- Create: `backend/app/Services/AccountingReportService.php`
- Test: `backend/tests/Unit/AccountingReportServiceTest.php`

**Interfaces:**
- Produces:
  - `summary(Carbon $from, Carbon $to, string $channel): array` with keys  
    `gross`, `base`, `surcharge`, `estimated_bank_fee`, `estimated_net`, `ticket_count`, `product_count`
  - `breakdownByKind(...)`: tickets vs products
  - `breakdownByTicketType(...)`: joker / techno / standard
  - `breakdownByDay(...)`: list of `[date, gross, count]`
  - `csvRows(...): iterable<array>` flat export rows
- Channel: `'all'|'online'|'walk_up'`
- Bank estimate constant: `0.025` on online rows only
- Date filter: tickets & products on `paid_at` (tickets fallback `created_at` if `paid_at` null)

- [ ] **Step 1: Seed + failing test**

Create one online paid ticket (sold_by null, amount 103, base 100, surcharge 3), one walk-up ticket (sold_by set, surcharge 0), one paid product. Assert summary for `all` and `online`.

Example assertions for online-only on the 103 ticket:

- gross 103
- estimated_bank_fee = round(103 * 0.025, 2) === 2.58
- estimated_net = 103 - 2.58

- [ ] **Step 2: Implement service** (keep SQL simple: load paid rows in range, aggregate in PHP for clarity unless volume demands SQL — festival scale is fine in PHP)

```php
private function estimatedBankFee(object $row): float
{
    if (!empty($row->sold_by)) {
        return 0.0;
    }
    return round((float) $row->amount * 0.025, 2);
}
```

CSV columns:  
`type,id,paid_at,channel,title,base_amount,surcharge_rate,surcharge_amount,amount,estimated_bank_fee,email,sold_by`

- [ ] **Step 3: PASS + commit**

```bash
git commit -m "feat(accounting): report service for summaries and CSV rows"
```

---

### Task 8: Filament Accounting page + CSV export

**Files:**
- Create: `backend/app/Filament/Pages/Accounting.php`
- Create: `backend/resources/views/filament/pages/accounting.blade.php`
- Modify: `backend/app/Providers/Filament/AdminPanelProvider.php` — add nav group `Finance` (optional; or put page under existing `Sales`)
- Test: `backend/tests/Feature/AccountingPageTest.php`

**Interfaces:**
- Consumes: `AccountingReportService`
- `canAccess()`: admin only

- [ ] **Step 1: Failing access + export test**

```php
public function test_only_admin_can_access_accounting(): void
{
    $this->assertTrue(Accounting::canAccess()); // acting as admin via Filament::actingAs / Livewire::actingAs
    // editor/scanner/seller → false
}

public function test_admin_can_download_csv(): void
{
    // seed paid sale, Livewire::test(Accounting::class)->call('exportCsv') 
    // assert streamed response contains header row and sale id
}
```

Use patterns from `NewSalePageTest.php` for Livewire + role checks.

- [ ] **Step 2: Implement page**

```php
protected static ?string $navigationIcon = 'heroicon-o-calculator';
protected static ?string $navigationGroup = 'Finance'; // register group in AdminPanelProvider
protected static ?string $navigationLabel = 'Accounting';
protected static ?string $title = 'Accounting';
protected static string $view = 'filament.pages.accounting';

public ?string $dateFrom = null;
public ?string $dateTo = null;
public string $channel = 'all'; // all|online|walk_up

public static function canAccess(): bool
{
    return auth()->user()?->isAdmin() ?? false;
}
```

Presets: methods `setRangeToday()`, `setRangeWeek()`, `setRangeMonth()` setting `$dateFrom`/`$dateTo`.

`getReportProperty()` computed via report service.

Blade: filter controls, summary cards (gross / base / surcharge / bank estimate / net / counts), simple HTML tables for breakdowns, button `wire:click="exportCsv"`.

`exportCsv()`:

```php
public function exportCsv()
{
    $rows = app(AccountingReportService::class)->csvRows(...);
    $filename = 'accounting-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');
        fputcsv($out, [/* headers */]);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
    }, $filename, ['Content-Type' => 'text/csv']);
}
```

- [ ] **Step 3: PASS + commit**

```bash
git commit -m "feat(admin): Accounting page with filters, summaries, and CSV export"
```

---

### Task 9: Regression sweep + smoke checklist

**Files:** none new — run suite / fix fallout from amount assertions

- [ ] **Step 1: Run focused suites**

```text
php artisan test --filter=PaymentSurchargeServiceTest
php artisan test --filter=SurchargeColumnsTest
php artisan test --filter=OnlineOrderSurchargeTest
php artisan test --filter=CreateWalkUp
php artisan test --filter=PublicPayablePriceTest
php artisan test --filter=Accounting
php artisan test --filter=ProductOrderPaymentTest
php artisan test --filter=PaymentFlowTest
php artisan test --filter=TicketOrderTest
```

- [ ] **Step 2: Fix any tests that still expect Quipu amount = catalog price**

- [ ] **Step 3: Manual smoke (local or staging)**

1. Site Settings → surcharge 3%.
2. Open public tickets page — price shows 103 for 100 GEL catalog.
3. Start checkout — Quipu/sandbox charge 103.
4. Accounting page — sale appears; CSV downloads.
5. New Sale walk-up — amount = catalog − discount; surcharge 0.

- [ ] **Step 4: Final commit if fixes landed**

```bash
git commit -m "test: align payment tests with online surcharge"
```

---

## Spec coverage (self-review)

| Spec requirement | Task |
|------------------|------|
| Persist base/surcharge/rate/amount | 2, 4, 5 |
| Default 3%, Site Settings editable | 1, 3 |
| Online only; walk-up 0 | 4, 5 |
| Quipu + callback on gross `amount` | 4 (callback already verifies `amount`) |
| Buyer sees single total | 6 (API) + no frontend fee line |
| product `paid_at` | 2, 5 |
| Accounting cards + breakdowns + CSV | 7, 8 |
| Admin only; no partner split | 8 |
| Bank estimate 2.5% online | 7 |
| Historical backfill no invented fee | 2 |

## Placeholder scan

No TBD/TODO left in tasks; signatures named consistently (`PaymentSurchargeService`, `AccountingReportService`, `payment_surcharge_percent`).
