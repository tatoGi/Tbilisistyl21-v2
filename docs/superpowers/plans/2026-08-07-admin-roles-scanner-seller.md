# Admin Roles: Scanner + Seller Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `scanner` and `seller` roles to the Filament admin panel, gated so each role only sees its own page, plus a bulk scanner-account generator and an in-person "walk-up sale" (POS) flow for sellers.

**Architecture:** `users.role` (existing plain string column, no DB enum) gets two new values. Filament page/resource visibility is gated per-page via `canAccess()` overrides — the existing single admin panel, no new guard/panel needed. Walk-up sales bypass the Quipu gateway entirely: a new Action creates an already-`paid` `SoldTicket`/`ProductOrder` row directly and reuses the existing `SendTicketEmailJob`/`SendProductOrderEmailJob` so the buyer still gets their QR by email.

**Tech Stack:** Laravel 11, Filament 3.3, Livewire 3 (via Filament), PostgreSQL. PHPUnit feature tests using `RefreshDatabase` and Filament's `Livewire::test()` helpers.

## Global Constraints

- Migrations must be additive/nullable only — no dropped/renamed columns, no data loss on existing rows (project convention, see `docs/superpowers/specs/2026-08-07-admin-roles-scanner-seller-design.md`).
- One role per user; no permission-matrix/multi-role system.
- Discount is a flat GEL amount (not a percentage).
- Walk-up sales never touch `PaymentService`/Quipu — mark paid immediately, no gateway call.
- Reuse `SendTicketEmailJob`/`SendProductOrderEmailJob` for buyer notification — do not write new email code.
- Follow existing patterns: `AdminOnlyResource` trait for admin-only resources, `Forms\Get`/`Forms\Set` inline (via `use Filament\Forms;`) not separate imports, raw `DB::table()->decrement()` + manual model refresh for stock changes (matches `ProcessPaymentCallbackAction`).
- `users.role` is deliberately `guarded` against mass assignment (see `AdminSeeder.php` comment) — any code setting `role` must use direct property assignment (`$user->role = ...`) or `forceFill()`, never `create()`/`update()` with `role` in the array.

---

### Task 1: User model — scanner/seller roles

**Files:**
- Modify: `backend/app/Models/User.php`
- Test: `backend/tests/Feature/UserRoleAccessTest.php`

**Interfaces:**
- Produces: `User::isScanner(): bool`, `User::isSeller(): bool` — used by Tasks 3, 4, 7 for `canAccess()` checks.

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Feature/UserRoleAccessTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec laravel php artisan test --filter=UserRoleAccessTest` (or `php artisan test --filter=UserRoleAccessTest` if running locally without Docker)
Expected: FAIL — `Call to undefined method App\Models\User::isScanner()`, and `canAccessPanel` returns false for `scanner`/`seller` (currently only checks `isAdmin()`/`isEditor()`).

- [ ] **Step 3: Implement**

In `backend/app/Models/User.php`, replace:

```php
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isEditor();
    }
```

with:

```php
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isScanner(): bool
    {
        return $this->role === 'scanner';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'editor', 'scanner', 'seller'], true);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec laravel php artisan test --filter=UserRoleAccessTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Models/User.php backend/tests/Feature/UserRoleAccessTest.php
git commit -m "feat(admin): add scanner/seller roles to User model"
```

---

### Task 2: `sold_by`/`discount_amount` columns

**Files:**
- Create: `backend/database/migrations/2026_08_07_000000_add_sold_by_and_discount_to_orders_tables.php`
- Modify: `backend/app/Models/SoldTicket.php`
- Modify: `backend/app/Models/ProductOrder.php`
- Test: `backend/tests/Feature/WalkUpSaleColumnsTest.php`

**Interfaces:**
- Produces: `sold_tickets.sold_by`, `sold_tickets.discount_amount`, `product_orders.sold_by`, `product_orders.discount_amount` (all nullable) — used by Tasks 5, 6.

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Feature/WalkUpSaleColumnsTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec laravel php artisan test --filter=WalkUpSaleColumnsTest`
Expected: FAIL — columns don't exist yet.

- [ ] **Step 3: Write the migration**

Create `backend/database/migrations/2026_08_07_000000_add_sold_by_and_discount_to_orders_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->string('sold_by')->nullable()->after('scanned_by');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('amount');
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->string('sold_by')->nullable()->after('status');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->dropColumn(['sold_by', 'discount_amount']);
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn(['sold_by', 'discount_amount']);
        });
    }
};
```

- [ ] **Step 4: Update the models**

In `backend/app/Models/SoldTicket.php`, change the `$fillable` array from:

```php
    protected $fillable = [
        'id', 'personal_number', 'email', 'name', 'surname', 'amount',
        'status', 'original_ticket_id', 'event_name', 'event_date',
        'location', 'paid_at', 'scanned_at', 'scanned_by',
        'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
        'failed_at', 'fail_reason', 'is_joker', 'is_techno',
    ];
```

to:

```php
    protected $fillable = [
        'id', 'personal_number', 'email', 'name', 'surname', 'amount',
        'status', 'original_ticket_id', 'event_name', 'event_date',
        'location', 'paid_at', 'scanned_at', 'scanned_by',
        'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
        'failed_at', 'fail_reason', 'is_joker', 'is_techno',
        'sold_by', 'discount_amount',
    ];
```

and change the `casts()` method from:

```php
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'event_date' => 'date',
            'paid_at' => 'datetime',
            'scanned_at' => 'datetime',
            'failed_at' => 'datetime',
            'is_joker' => 'boolean',
            'is_techno' => 'boolean',
        ];
    }
```

to:

```php
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'event_date' => 'date',
            'paid_at' => 'datetime',
            'scanned_at' => 'datetime',
            'failed_at' => 'datetime',
            'is_joker' => 'boolean',
            'is_techno' => 'boolean',
        ];
    }
```

In `backend/app/Models/ProductOrder.php`, change the `$fillable` array from:

```php
    protected $fillable = [
        'id', 'product_id', 'product_title', 'size', 'name', 'surname', 'personal_number',
        'email', 'phone', 'amount', 'status', 'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
    ];
```

to:

```php
    protected $fillable = [
        'id', 'product_id', 'product_title', 'size', 'name', 'surname', 'personal_number',
        'email', 'phone', 'amount', 'status', 'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
        'sold_by', 'discount_amount',
    ];
```

and change `casts()` from `return ['amount' => 'decimal:2'];` to:

```php
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }
```

- [ ] **Step 5: Run the migration and test**

Run: `docker compose exec laravel php artisan migrate` then `docker compose exec laravel php artisan test --filter=WalkUpSaleColumnsTest`
Expected: migration runs clean, test PASSes.

- [ ] **Step 6: Commit**

```bash
git add backend/database/migrations/2026_08_07_000000_add_sold_by_and_discount_to_orders_tables.php backend/app/Models/SoldTicket.php backend/app/Models/ProductOrder.php backend/tests/Feature/WalkUpSaleColumnsTest.php
git commit -m "feat(admin): add sold_by/discount_amount columns for walk-up sales"
```

---

### Task 3: Scanner accountability + gate the Ticket Scanner page

The scanner page already exists (`backend/app/Filament/Pages/TicketScanner.php`) but currently has **no `canAccess()` override** — any authenticated Filament user (including `editor`) can reach it — and `ValidateTicketAction` hardcodes `scanned_by = 'admin'` for every scan, so with 30 door staff there's no way to tell who scanned what.

**Files:**
- Modify: `backend/app/Actions/ValidateTicketAction.php`
- Modify: `backend/app/Http/Controllers/Admin/TicketScannerController.php`
- Modify: `backend/app/Filament/Pages/TicketScanner.php`
- Test: `backend/tests/Feature/TicketScannerPageTest.php`

**Interfaces:**
- Consumes: `User::isScanner()` (Task 1)
- Produces: `ValidateTicketAction::execute(array $qrData, string $scannedBy): array` (signature changed — both existing callers updated in this task)

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Feature/TicketScannerPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Filament\Pages\TicketScanner;
use App\Models\SoldTicket;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketScannerPageTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidTicket(): SoldTicket
    {
        return SoldTicket::create([
            'id' => 'PAGE0001',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'paid',
            'paid_at' => now(),
            'original_ticket_id' => '11111111-1111-1111-1111-111111111111',
            'event_name' => 'Test Concert',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);
    }

    public function test_scanner_role_can_access_page(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner']);

        $this->actingAs($scanner)
            ->get('/admin/ticket-scanner')
            ->assertOk();
    }

    public function test_editor_role_cannot_access_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->get('/admin/ticket-scanner')
            ->assertForbidden();
    }

    public function test_scan_records_authenticated_users_name(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner', 'name' => 'Nino Beridze']);
        $ticket = $this->createPaidTicket();

        $json = app(QrCodeService::class)->generateTicketData(
            $ticket->id,
            $ticket->personal_number,
            $ticket->original_ticket_id,
        );

        Livewire::actingAs($scanner)
            ->test(TicketScanner::class)
            ->call('scan', $json)
            ->assertSet('success', true);

        $ticket->refresh();
        $this->assertEquals('Nino Beridze', $ticket->scanned_by);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec laravel php artisan test --filter=TicketScannerPageTest`
Expected: FAIL — `test_editor_role_cannot_access_page` fails (no `canAccess()` gate yet, editor gets 200 not 403), and `scan()` doesn't accept a second argument yet.

- [ ] **Step 3: Update `ValidateTicketAction`**

In `backend/app/Actions/ValidateTicketAction.php`, change the method signature and the `scanned_by` value:

```php
    public function execute(array $qrData, string $scannedBy): array
```

(was `public function execute(array $qrData): array`), and inside, change:

```php
            ->update([
                'scanned_at' => now(),
                'scanned_by' => 'admin',
                'status' => 'scanned',
            ]);
```

to:

```php
            ->update([
                'scanned_at' => now(),
                'scanned_by' => $scannedBy,
                'status' => 'scanned',
            ]);
```

- [ ] **Step 4: Update `TicketScannerController`**

In `backend/app/Http/Controllers/Admin/TicketScannerController.php`, change:

```php
        $result = $action->execute($request->validated());
```

to:

```php
        $result = $action->execute($request->validated(), $request->user()->name);
```

- [ ] **Step 5: Update `TicketScanner` Filament page**

In `backend/app/Filament/Pages/TicketScanner.php`, change:

```php
        $outcome = app(ValidateTicketAction::class)->execute($decoded);
```

to:

```php
        $outcome = app(ValidateTicketAction::class)->execute($decoded, auth()->user()->name);
```

Then add a `canAccess()` override to the class (alongside the existing properties/methods):

```php
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isScanner());
    }
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `docker compose exec laravel php artisan test --filter=TicketScannerPageTest`
Expected: PASS (3 tests)

Also re-run the existing scanner API test to confirm the signature change didn't break it:

Run: `docker compose exec laravel php artisan test --filter=TicketScannerTest`
Expected: PASS (all existing tests, unchanged behavior — they don't assert on `scanned_by`'s value)

- [ ] **Step 7: Commit**

```bash
git add backend/app/Actions/ValidateTicketAction.php backend/app/Http/Controllers/Admin/TicketScannerController.php backend/app/Filament/Pages/TicketScanner.php backend/tests/Feature/TicketScannerPageTest.php
git commit -m "fix(admin): attribute scans to the acting user, restrict scanner page to admin/scanner"
```

---

### Task 4: `UserResource` — user management + bulk scanner generation

**Files:**
- Create: `backend/app/Filament/Resources/UserResource.php`
- Create: `backend/app/Filament/Resources/UserResource/Pages/ListUsers.php`
- Create: `backend/app/Filament/Resources/UserResource/Pages/CreateUser.php`
- Create: `backend/app/Filament/Resources/UserResource/Pages/EditUser.php`
- Modify: `backend/app/Providers/Filament/AdminPanelProvider.php`
- Test: `backend/tests/Feature/UserResourceTest.php`

**Interfaces:**
- Consumes: `AdminOnlyResource` trait (existing), `User::isScanner()`/`isSeller()` not needed here (this resource is admin-only)

- [ ] **Step 1: Write the failing tests**

Create `backend/tests/Feature/UserResourceTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec laravel php artisan test --filter=UserResourceTest`
Expected: FAIL — `/admin/users` route doesn't exist yet (404), `CreateUser`/`ListUsers` classes don't exist.

- [ ] **Step 3: Create the resource**

Create `backend/app/Filament/Resources/UserResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnlyResource;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Team';

    protected static ?int $navigationSort = 1;

    public const ROLES = [
        'admin' => 'Admin',
        'editor' => 'Editor',
        'scanner' => 'Scanner',
        'seller' => 'Seller',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\Select::make('role')
                ->options(self::ROLES)
                ->required()
                ->default('editor'),
            Forms\Components\TextInput::make('password')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->maxLength(255)
                ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->label(fn (string $context): string => $context === 'create' ? 'Password' : 'New password'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        'scanner' => 'info',
                        'seller' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 4: Create the List page with the bulk-generate action**

Create `backend/app/Filament/Resources/UserResource/Pages/ListUsers.php`:

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateScanners')
                ->label('Generate scanner accounts')
                ->icon('heroicon-o-qr-code')
                ->form([
                    Forms\Components\TextInput::make('count')
                        ->label('How many accounts')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(10),
                ])
                ->action(function (array $data): void {
                    $numbers = $this->nextScannerNumbers((int) $data['count']);
                    $lines = [];

                    foreach ($numbers as $n) {
                        $email = sprintf('scanner%02d@tbilisistyle.ge', $n);
                        $password = Str::random(8);

                        $user = new User([
                            'name' => "Scanner {$n}",
                            'email' => $email,
                            'password' => Hash::make($password),
                        ]);
                        $user->role = 'scanner';
                        $user->save();

                        $lines[] = "{$email} / {$password}";
                    }

                    Notification::make()
                        ->title('Scanner accounts created — copy these now, passwords are not shown again')
                        ->body(implode("\n", $lines))
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    private function nextScannerNumbers(int $count): array
    {
        $existing = User::where('email', 'like', 'scanner%@tbilisistyle.ge')
            ->pluck('email')
            ->map(fn (string $email): int => (int) preg_replace('/\D/', '', explode('@', $email)[0]))
            ->filter()
            ->values();

        $start = $existing->isEmpty() ? 1 : $existing->max() + 1;

        return range($start, $start + $count - 1);
    }
}
```

- [ ] **Step 5: Create the Create/Edit pages with role `forceFill`**

`users.role` is guarded against mass assignment (see `AdminSeeder.php`), so the default Filament `CreateRecord`/`EditRecord` behavior (`Model::create($data)` / `$record->update($data)`) would silently drop it. Override both.

Create `backend/app/Filament/Resources/UserResource/Pages/CreateUser.php`:

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? 'editor';
        unset($data['role']);

        $user = User::create($data);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
```

Create `backend/app/Filament/Resources/UserResource/Pages/EditUser.php`:

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $role = $data['role'] ?? $record->role;
        unset($data['role']);

        $record->update($data);
        $record->forceFill(['role' => $role])->save();

        return $record;
    }
}
```

- [ ] **Step 6: Register the "Team" nav group**

In `backend/app/Providers/Filament/AdminPanelProvider.php`, change:

```php
            ->navigationGroups([
                NavigationGroup::make('Content')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Catalog')->icon('heroicon-o-squares-2x2'),
                NavigationGroup::make('Sales')->icon('heroicon-o-banknotes'),
            ])
```

to:

```php
            ->navigationGroups([
                NavigationGroup::make('Team')->icon('heroicon-o-user-group'),
                NavigationGroup::make('Content')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Catalog')->icon('heroicon-o-squares-2x2'),
                NavigationGroup::make('Sales')->icon('heroicon-o-banknotes'),
            ])
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `docker compose exec laravel php artisan test --filter=UserResourceTest`
Expected: PASS (4 tests)

- [ ] **Step 8: Commit**

```bash
git add backend/app/Filament/Resources/UserResource.php backend/app/Filament/Resources/UserResource/Pages backend/app/Providers/Filament/AdminPanelProvider.php backend/tests/Feature/UserResourceTest.php
git commit -m "feat(admin): add UserResource with bulk scanner-account generation"
```

---

### Task 5: `CreateWalkUpTicketSaleAction`

**Files:**
- Create: `backend/app/Actions/CreateWalkUpTicketSaleAction.php`
- Test: `backend/tests/Feature/CreateWalkUpTicketSaleActionTest.php`

**Interfaces:**
- Consumes: `sold_tickets.sold_by`/`discount_amount` (Task 2), `App\Services\QrCodeService::generateTicketData()` (existing), `App\Jobs\SendTicketEmailJob::dispatch()` (existing)
- Produces: `CreateWalkUpTicketSaleAction::execute(array $data): array` where `$data` has keys `ticketId, name, surname, personalNumber, email, discountAmount, soldBy` and the return is `['soldTicket' => SoldTicket, 'status' => 200]` on success or `['error' => string, 'status' => int]` on failure. Used by Task 7.

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Feature/CreateWalkUpTicketSaleActionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Actions\CreateWalkUpTicketSaleAction;
use App\Jobs\SendTicketEmailJob;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CreateWalkUpTicketSaleActionTest extends TestCase
{
    use RefreshDatabase;

    private function createTicket(int $quantity = 5): Ticket
    {
        return Ticket::create([
            'title' => ['ka' => 'ტესტ ბილეთი', 'en' => 'Test Ticket'],
            'description' => ['ka' => '', 'en' => ''],
            'price_gel' => 100,
            'quantity' => $quantity,
            'status' => 'active',
            'event_date' => '2026-08-20',
            'location' => 'Tbilisi',
        ]);
    }

    public function test_creates_paid_ticket_with_discount_and_seller_attribution(): void
    {
        Bus::fake();

        $ticket = $this->createTicket();

        $result = app(CreateWalkUpTicketSaleAction::class)->execute([
            'ticketId' => $ticket->id,
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'discountAmount' => 20,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(200, $result['status']);
        $soldTicket = $result['soldTicket'];
        $this->assertEquals('paid', $soldTicket->status);
        $this->assertEquals(80, (float) $soldTicket->amount);
        $this->assertEquals(20, (float) $soldTicket->discount_amount);
        $this->assertEquals('Nino Seller', $soldTicket->sold_by);

        $ticket->refresh();
        $this->assertEquals(4, $ticket->quantity);

        Bus::assertDispatched(SendTicketEmailJob::class);
    }

    public function test_returns_sold_out_when_ticket_has_no_quantity(): void
    {
        $ticket = $this->createTicket(quantity: 0);

        $result = app(CreateWalkUpTicketSaleAction::class)->execute([
            'ticketId' => $ticket->id,
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'discountAmount' => 0,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('sold_out', $result['error']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec laravel php artisan test --filter=CreateWalkUpTicketSaleActionTest`
Expected: FAIL — class doesn't exist.

- [ ] **Step 3: Implement the action**

Create `backend/app/Actions/CreateWalkUpTicketSaleAction.php`:

```php
<?php

namespace App\Actions;

use App\Jobs\SendTicketEmailJob;
use App\Models\JokerTicket;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWalkUpTicketSaleAction
{
    public function __construct(private QrCodeService $qrCodeService) {}

    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::where('id', $data['ticketId'])->lockForUpdate()->firstOrFail();

            if ($ticket->status !== 'active' || $ticket->quantity <= 0) {
                return ['error' => 'sold_out', 'status' => 400];
            }

            $decremented = DB::table('tickets')
                ->where('id', $ticket->id)
                ->where('status', 'active')
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                return ['error' => 'sold_out', 'status' => 400];
            }

            $ticket->refresh();
            if ($ticket->quantity <= 0) {
                $ticket->update(['status' => 'sold_out']);
            }

            $discount = (float) ($data['discountAmount'] ?? 0);
            $finalAmount = max(0, (float) $ticket->price_gel - $discount);

            $internalId = strtoupper(Str::random(8));

            $qrData = $this->qrCodeService->generateTicketData(
                $internalId,
                $data['personalNumber'],
                $ticket->id,
            );

            $soldTicket = SoldTicket::create([
                'id' => $internalId,
                'personal_number' => $data['personalNumber'],
                'email' => $data['email'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'amount' => $finalAmount,
                'discount_amount' => $discount > 0 ? $discount : null,
                'sold_by' => $data['soldBy'],
                'status' => 'paid',
                'paid_at' => now(),
                'original_ticket_id' => $ticket->id,
                'event_name' => $ticket->setLocale('ka')->title,
                'is_joker' => $ticket->is_joker,
                'is_techno' => $ticket->is_techno,
                'event_date' => $ticket->event_date,
                'location' => $ticket->location,
                'qr_code' => $qrData,
            ]);

            if ($soldTicket->isJokerEvent()) {
                JokerTicket::create([
                    'sold_ticket_id' => $soldTicket->id,
                    'personal_number' => $soldTicket->personal_number,
                    'email' => $soldTicket->email,
                    'name' => $soldTicket->name,
                    'surname' => $soldTicket->surname,
                ]);
            }

            SendTicketEmailJob::dispatch($soldTicket->id);

            return ['soldTicket' => $soldTicket, 'status' => 200];
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec laravel php artisan test --filter=CreateWalkUpTicketSaleActionTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Actions/CreateWalkUpTicketSaleAction.php backend/tests/Feature/CreateWalkUpTicketSaleActionTest.php
git commit -m "feat(admin): add CreateWalkUpTicketSaleAction for in-person ticket sales"
```

---

### Task 6: `CreateWalkUpProductSaleAction`

**Files:**
- Create: `backend/app/Actions/CreateWalkUpProductSaleAction.php`
- Test: `backend/tests/Feature/CreateWalkUpProductSaleActionTest.php`

**Interfaces:**
- Consumes: `product_orders.sold_by`/`discount_amount` (Task 2), `App\Jobs\SendProductOrderEmailJob::dispatch()` (existing)
- Produces: `CreateWalkUpProductSaleAction::execute(array $data): array` where `$data` has keys `productId, size, name, surname, personalNumber, email, phone, discountAmount, soldBy`, returning `['productOrder' => ProductOrder, 'status' => 200]` or `['error' => string, 'status' => int]`. Used by Task 7.

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Feature/CreateWalkUpProductSaleActionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Actions\CreateWalkUpProductSaleAction;
use App\Jobs\SendProductOrderEmailJob;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CreateWalkUpProductSaleActionTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithSize(int $quantity = 5): array
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-shirt'],
            'description' => ['ka' => '', 'en' => ''],
            'price_gel' => 50,
            'status' => 'active',
        ]);

        $size = ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => $quantity,
        ]);

        return [$product, $size];
    }

    public function test_creates_paid_product_order_with_discount_and_seller_attribution(): void
    {
        Bus::fake();

        [$product, $size] = $this->createProductWithSize();

        $result = app(CreateWalkUpProductSaleAction::class)->execute([
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'phone' => '+995500000000',
            'discountAmount' => 5,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(200, $result['status']);
        $order = $result['productOrder'];
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(45, (float) $order->amount);
        $this->assertEquals(5, (float) $order->discount_amount);
        $this->assertEquals('Nino Seller', $order->sold_by);

        $size->refresh();
        $this->assertEquals(4, $size->quantity);

        Bus::assertDispatched(SendProductOrderEmailJob::class);
    }

    public function test_returns_size_sold_out_when_no_stock(): void
    {
        [$product, $size] = $this->createProductWithSize(quantity: 0);

        $result = app(CreateWalkUpProductSaleAction::class)->execute([
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'phone' => '+995500000000',
            'discountAmount' => 0,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('size_sold_out', $result['error']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec laravel php artisan test --filter=CreateWalkUpProductSaleActionTest`
Expected: FAIL — class doesn't exist.

- [ ] **Step 3: Implement the action**

Create `backend/app/Actions/CreateWalkUpProductSaleAction.php`:

```php
<?php

namespace App\Actions;

use App\Jobs\SendProductOrderEmailJob;
use App\Models\Product;
use App\Models\ProductOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWalkUpProductSaleAction
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $product = Product::with('sizes')->findOrFail($data['productId']);

            if ($product->status !== 'active') {
                return ['error' => 'product_unavailable', 'status' => 400];
            }

            $size = $product->sizes->where('size', $data['size'])->first();

            if (!$size || $size->quantity <= 0) {
                return ['error' => 'size_sold_out', 'status' => 400];
            }

            $decremented = DB::table('product_sizes')
                ->where('product_id', $product->id)
                ->where('size', $data['size'])
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                return ['error' => 'size_sold_out', 'status' => 400];
            }

            Cache::forget(Product::API_CACHE_KEY);

            $discount = (float) ($data['discountAmount'] ?? 0);
            $finalAmount = max(0, (float) $product->price_gel - $discount);

            $internalId = strtoupper(Str::random(8));

            $order = ProductOrder::create([
                'id' => $internalId,
                'product_id' => $product->id,
                'product_title' => $product->setLocale('ka')->title,
                'size' => $data['size'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'personal_number' => $data['personalNumber'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'amount' => $finalAmount,
                'discount_amount' => $discount > 0 ? $discount : null,
                'sold_by' => $data['soldBy'],
                'status' => 'paid',
            ]);

            SendProductOrderEmailJob::dispatch($order->id);

            return ['productOrder' => $order, 'status' => 200];
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec laravel php artisan test --filter=CreateWalkUpProductSaleActionTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Actions/CreateWalkUpProductSaleAction.php backend/tests/Feature/CreateWalkUpProductSaleActionTest.php
git commit -m "feat(admin): add CreateWalkUpProductSaleAction for in-person product sales"
```

---

### Task 7: `NewSale` Filament page

**Files:**
- Create: `backend/app/Filament/Pages/NewSale.php`
- Create: `backend/resources/views/filament/pages/new-sale.blade.php`
- Test: `backend/tests/Feature/NewSalePageTest.php`

**Interfaces:**
- Consumes: `User::isSeller()` (Task 1), `CreateWalkUpTicketSaleAction::execute()` (Task 5), `CreateWalkUpProductSaleAction::execute()` (Task 6)

- [ ] **Step 1: Write the failing tests**

Create `backend/tests/Feature/NewSalePageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Filament\Pages\NewSale;
use App\Jobs\SendTicketEmailJob;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class NewSalePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_role_cannot_access_new_sale_page(): void
    {
        $scanner = User::factory()->create(['role' => 'scanner']);

        $this->actingAs($scanner)
            ->get('/admin/new-sale')
            ->assertForbidden();
    }

    public function test_seller_can_access_new_sale_page(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->get('/admin/new-sale')
            ->assertOk();
    }

    public function test_seller_can_record_a_ticket_sale(): void
    {
        Bus::fake();

        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Nino Seller']);

        $ticket = Ticket::create([
            'title' => ['ka' => 'ტესტ ბილეთი', 'en' => 'Test Ticket'],
            'description' => ['ka' => '', 'en' => ''],
            'price_gel' => 100,
            'quantity' => 5,
            'status' => 'active',
            'event_date' => '2026-08-20',
            'location' => 'Tbilisi',
        ]);

        Livewire::actingAs($seller)
            ->test(NewSale::class)
            ->fillForm([
                'type' => 'ticket',
                'ticketId' => $ticket->id,
                'name' => 'Giorgi',
                'surname' => 'Beridze',
                'personalNumber' => '01011011011',
                'email' => 'giorgi@example.com',
                'discountAmount' => 10,
            ])
            ->call('create');

        $this->assertDatabaseHas('sold_tickets', [
            'personal_number' => '01011011011',
            'status' => 'paid',
            'sold_by' => 'Nino Seller',
            'amount' => 90,
        ]);

        Bus::assertDispatched(SendTicketEmailJob::class);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec laravel php artisan test --filter=NewSalePageTest`
Expected: FAIL — `NewSale` class/route doesn't exist yet.

- [ ] **Step 3: Implement the page**

Create `backend/app/Filament/Pages/NewSale.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Actions\CreateWalkUpProductSaleAction;
use App\Actions\CreateWalkUpTicketSaleAction;
use App\Models\Product;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NewSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'New Sale';

    protected static ?string $title = 'New Sale';

    protected static string $view = 'filament.pages.new-sale';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isSeller());
    }

    public function mount(): void
    {
        $this->form->fill(['type' => 'ticket', 'discountAmount' => 0]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options(['ticket' => 'Ticket', 'product' => 'Product'])
                    ->required()
                    ->live()
                    ->default('ticket'),
                Forms\Components\Select::make('ticketId')
                    ->label('Ticket')
                    ->options(fn () => Ticket::active()->get()->mapWithKeys(
                        fn (Ticket $t) => [$t->id => $t->setLocale('ka')->title . ' — ' . $t->price_gel . ' GEL']
                    ))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'ticket')
                    ->required(fn (Forms\Get $get) => $get('type') === 'ticket'),
                Forms\Components\Select::make('productId')
                    ->label('Product')
                    ->options(fn () => Product::active()->get()->mapWithKeys(
                        fn (Product $p) => [$p->id => $p->setLocale('ka')->title]
                    ))
                    ->live()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'product')
                    ->required(fn (Forms\Get $get) => $get('type') === 'product'),
                Forms\Components\Select::make('size')
                    ->options(function (Forms\Get $get) {
                        $product = Product::with('sizes')->find($get('productId'));

                        return $product
                            ? $product->sizes->pluck('size', 'size')
                            : [];
                    })
                    ->visible(fn (Forms\Get $get) => $get('type') === 'product')
                    ->required(fn (Forms\Get $get) => $get('type') === 'product'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('surname')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('personalNumber')
                    ->label('Personal number')
                    ->required()
                    ->minLength(11)
                    ->maxLength(11),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'product')
                    ->required(fn (Forms\Get $get) => $get('type') === 'product'),
                Forms\Components\TextInput::make('discountAmount')
                    ->label('Discount (GEL)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $soldBy = auth()->user()->name;

        if ($data['type'] === 'ticket') {
            $result = app(CreateWalkUpTicketSaleAction::class)->execute([
                'ticketId' => $data['ticketId'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'personalNumber' => $data['personalNumber'],
                'email' => $data['email'],
                'discountAmount' => $data['discountAmount'] ?? 0,
                'soldBy' => $soldBy,
            ]);
        } else {
            $result = app(CreateWalkUpProductSaleAction::class)->execute([
                'productId' => $data['productId'],
                'size' => $data['size'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'personalNumber' => $data['personalNumber'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'discountAmount' => $data['discountAmount'] ?? 0,
                'soldBy' => $soldBy,
            ]);
        }

        if ($result['status'] !== 200) {
            Notification::make()
                ->title('Sale failed: ' . $result['error'])
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Sale recorded — ticket emailed to the buyer')
            ->success()
            ->send();

        $this->form->fill(['type' => 'ticket', 'discountAmount' => 0]);
    }
}
```

- [ ] **Step 4: Create the view**

Create `backend/resources/views/filament/pages/new-sale.blade.php`:

```blade
<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Record Sale
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `docker compose exec laravel php artisan test --filter=NewSalePageTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Run the full backend test suite**

Run: `docker compose exec laravel php artisan test`
Expected: PASS — all existing tests plus the new ones from Tasks 1–7 pass, nothing broken.

- [ ] **Step 7: Commit**

```bash
git add backend/app/Filament/Pages/NewSale.php backend/resources/views/filament/pages/new-sale.blade.php backend/tests/Feature/NewSalePageTest.php
git commit -m "feat(admin): add seller walk-up sale (POS) page"
```
