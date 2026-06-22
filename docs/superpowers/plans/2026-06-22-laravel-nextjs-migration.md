# Laravel + Next.js Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate TbilisiStyle21 from full-stack Next.js + Payload CMS to Laravel API backend + Next.js frontend with Filament admin panel, Sanctum auth, and Docker Compose deployment.

**Architecture:** Laravel serves as the API backend and admin panel (Filament). Next.js serves as the SSR frontend consuming Laravel's REST API. Both run on a single VPS via Docker Compose with Nginx reverse proxy. PostgreSQL is the shared database, managed by Laravel migrations.

**Tech Stack:** Laravel 12, PHP 8.3, Filament 3, Sanctum, spatie/laravel-translatable, PostgreSQL 16, Next.js (existing frontend), Docker Compose, Nginx, PgAdmin 4

## Global Constraints

- PHP 8.3+, Laravel 12.x
- PostgreSQL 16 (UUID primary keys)
- All translatable fields use JSON columns with `spatie/laravel-translatable`
- Languages: `ka` (default), `en`, `ru`, `ua`
- Encrypted fields use Laravel `Crypt::encryptString()` / `Crypt::decryptString()`
- All API responses are JSON with `Accept-Language` header for locale
- Rate limits enforced via Laravel `ThrottleRequests` middleware
- TDD: write failing test first, then implement
- Commit after each task completes

**Spec:** `docs/superpowers/specs/2026-06-22-laravel-nextjs-migration-design.md`

---

## Task 1: Docker Compose Dev Environment + Laravel Scaffold

**Files:**
- Create: `docker-compose.dev.yml`
- Create: `backend/.env.example`
- Create: `backend/` (Laravel project via `composer create-project`)

**Interfaces:**
- Produces: Running PostgreSQL at `localhost:5432`, PgAdmin at `localhost:5050`, Mailpit at `localhost:8025`, Laravel project scaffold at `backend/`

- [ ] **Step 1: Create docker-compose.dev.yml**

```yaml
# docker-compose.dev.yml
services:
  postgres:
    image: postgres:16-alpine
    ports:
      - "5432:5432"
    volumes:
      - pgdata-dev:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: tbilisistyle
      POSTGRES_USER: tbilisistyle
      POSTGRES_PASSWORD: secret
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U tbilisistyle"]
      interval: 5s
      timeout: 3s
      retries: 5

  pgadmin:
    image: dpage/pgadmin4
    ports:
      - "5050:80"
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@tbilisistyle.ge
      PGADMIN_DEFAULT_PASSWORD: admin
      PGADMIN_CONFIG_SERVER_MODE: "False"
    depends_on:
      postgres:
        condition: service_healthy
    volumes:
      - pgadmin-data:/var/lib/pgadmin

  mailpit:
    image: axllent/mailpit
    ports:
      - "8025:8025"
      - "1025:1025"

volumes:
  pgdata-dev:
  pgadmin-data:
```

- [ ] **Step 2: Start Docker containers**

Run: `docker compose -f docker-compose.dev.yml up -d`
Expected: 3 containers running. Verify:
- `http://localhost:5050` — PgAdmin login page
- `http://localhost:8025` — Mailpit inbox

- [ ] **Step 3: Configure PgAdmin server connection**

Open `http://localhost:5050`, login with `admin@tbilisistyle.ge` / `admin`.
Add server: Name=`tbilisistyle-dev`, Host=`postgres`, Port=`5432`, Username=`tbilisistyle`, Password=`secret`.
Verify you see the `tbilisistyle` database with public schema.

- [ ] **Step 4: Scaffold Laravel project**

Run:
```bash
cd /c/Users/pc/Desktop/tato/TbilisiStyle21
composer create-project laravel/laravel backend
```
Expected: `backend/` directory with fresh Laravel 12 install.

- [ ] **Step 5: Configure Laravel .env for local development**

Edit `backend/.env`:
```
APP_NAME=TbilisiStyle21
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tbilisistyle
DB_USERNAME=tbilisistyle
DB_PASSWORD=secret

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@tbilisistyle.ge"
MAIL_FROM_NAME="TbilisiStyle21"
```

- [ ] **Step 6: Install core packages**

Run:
```bash
cd backend
composer require laravel/sanctum filament/filament:^3.3 spatie/laravel-translatable filament/spatie-laravel-translatable-plugin spatie/laravel-activitylog barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode resend/resend-laravel
```

- [ ] **Step 7: Publish configs and install Filament**

Run:
```bash
cd backend
php artisan filament:install --panels
php artisan vendor:publish --provider="Spatie\Translatable\TranslatableServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

- [ ] **Step 8: Verify Laravel starts**

Run: `cd backend && php artisan serve`
Expected: `http://localhost:8000` shows Laravel welcome page.

- [ ] **Step 9: Verify database connection**

Run: `cd backend && php artisan migrate`
Expected: Default Laravel migrations run successfully. Check PgAdmin — you should see `users`, `sessions`, `cache`, `jobs` tables.

- [ ] **Step 10: Create .env.example**

Copy `backend/.env` to `backend/.env.example`, replace secrets with placeholders:
```
DB_PASSWORD=your_password_here
```

- [ ] **Step 11: Commit**

```bash
git add docker-compose.dev.yml backend/
git commit -m "feat: scaffold Laravel project + Docker dev environment (PostgreSQL, PgAdmin, Mailpit)"
```

---

## Task 2: Database Migrations & Models

**Files:**
- Create: `backend/database/migrations/xxxx_create_media_table.php`
- Create: `backend/database/migrations/xxxx_create_tickets_table.php`
- Create: `backend/database/migrations/xxxx_create_sold_tickets_table.php`
- Create: `backend/database/migrations/xxxx_create_products_table.php`
- Create: `backend/database/migrations/xxxx_create_product_sizes_table.php`
- Create: `backend/database/migrations/xxxx_create_product_orders_table.php`
- Create: `backend/database/migrations/xxxx_create_joker_tickets_table.php`
- Create: `backend/database/migrations/xxxx_create_music_tracks_table.php`
- Create: `backend/database/migrations/xxxx_create_pages_table.php`
- Create: `backend/database/migrations/xxxx_create_posts_table.php`
- Create: `backend/database/migrations/xxxx_create_partners_table.php`
- Create: `backend/database/migrations/xxxx_create_site_settings_table.php`
- Create: `backend/app/Models/Ticket.php` (and all other models)
- Modify: `backend/app/Models/User.php` — add role field, Sanctum trait
- Test: `backend/tests/Feature/ModelTest.php`

**Interfaces:**
- Produces: All Eloquent models with relationships, scopes, translatable traits. User model with `role` enum (admin/editor).

- [ ] **Step 1: Write model tests**

Create `backend/tests/Feature/ModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\SoldTicket;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductOrder;
use App\Models\MusicTrack;
use App\Models\Page;
use App\Models\Post;
use App\Models\Partner;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->assertEquals('admin', $user->role);
        $this->assertTrue($user->isAdmin());
    }

    public function test_ticket_is_translatable(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'ბილეთი', 'en' => 'Ticket'],
            'description' => ['ka' => 'აღწერა', 'en' => 'Description'],
            'price_gel' => 50,
            'quantity' => 100,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);
        $this->assertEquals('ბილეთი', $ticket->setLocale('ka')->title);
        $this->assertEquals('Ticket', $ticket->setLocale('en')->title);
    }

    public function test_ticket_active_scope(): void
    {
        Ticket::create([
            'title' => ['ka' => 'Active'],
            'price_gel' => 10,
            'quantity' => 5,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);
        Ticket::create([
            'title' => ['ka' => 'Draft'],
            'price_gel' => 10,
            'quantity' => 5,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'draft',
        ]);
        $this->assertCount(1, Ticket::active()->get());
    }

    public function test_product_has_sizes(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური'],
            'price_gel' => 30,
            'status' => 'active',
        ]);
        $product->sizes()->create(['size' => 'M', 'quantity' => 10]);
        $product->sizes()->create(['size' => 'L', 'quantity' => 5]);
        $this->assertCount(2, $product->sizes);
        $this->assertEquals(15, $product->sizes->sum('quantity'));
    }

    public function test_sold_ticket_belongs_to_no_user(): void
    {
        // SoldTickets are created by payment flow, not user-owned
        $sold = SoldTicket::create([
            'id' => 'ABCD1234',
            'personal_number' => '12345678901',
            'email' => 'test@test.com',
            'name' => 'John',
            'surname' => 'Doe',
            'amount' => 50,
            'status' => 'pending',
            'event_name' => 'Festival',
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
        ]);
        $this->assertEquals('pending', $sold->status);
    }

    public function test_music_track_ordered_scope(): void
    {
        MusicTrack::create(['title' => ['ka' => 'B'], 'artist' => 'X', 'order' => 2, 'status' => 'active']);
        MusicTrack::create(['title' => ['ka' => 'A'], 'artist' => 'Y', 'order' => 1, 'status' => 'active']);
        $tracks = MusicTrack::ordered()->get();
        $this->assertEquals('A', $tracks->first()->setLocale('ka')->title);
    }

    public function test_page_published_scope(): void
    {
        Page::create(['title' => ['ka' => 'Published'], 'slug' => 'pub', 'is_published' => true]);
        Page::create(['title' => ['ka' => 'Draft'], 'slug' => 'draft', 'is_published' => false]);
        $this->assertCount(1, Page::published()->get());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd backend && php artisan test --filter=ModelTest`
Expected: All tests FAIL (tables and models don't exist yet).

- [ ] **Step 3: Modify User model — add role**

Modify `backend/app/Models/User.php`:

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
}
```

Add role column to existing users migration. Create migration:

```php
// database/migrations/xxxx_add_role_to_users_table.php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('editor')->after('email');
    });
}
```

- [ ] **Step 4: Create Media migration and model**

Migration:
```php
Schema::create('media', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('filename');
    $table->string('path');
    $table->string('mime_type');
    $table->unsignedBigInteger('size');
    $table->string('alt')->nullable();
    $table->timestamps();
});
```

Model `backend/app/Models/Media.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUuids;

    protected $fillable = ['filename', 'path', 'mime_type', 'size', 'alt'];

    public function getUrlAttribute(): string
    {
        return '/storage/media/' . $this->filename;
    }
}
```

- [ ] **Step 5: Create Tickets migration and model**

Migration:
```php
Schema::create('tickets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->json('title');
    $table->json('description')->nullable();
    $table->decimal('price_gel', 10, 2);
    $table->integer('quantity')->default(0);
    $table->date('event_date');
    $table->string('location');
    $table->string('status')->default('draft'); // draft, active, sold_out
    $table->string('sale_url')->nullable();
    $table->timestamps();
});
```

Model `backend/app/Models/Ticket.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Ticket extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'title', 'description', 'price_gel', 'quantity',
        'event_date', 'location', 'status', 'sale_url',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'price_gel' => 'decimal:2',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
```

- [ ] **Step 6: Create SoldTickets migration and model**

Migration:
```php
Schema::create('sold_tickets', function (Blueprint $table) {
    $table->string('id', 8)->primary(); // 8-char UUID uppercase
    $table->string('personal_number', 11);
    $table->string('email');
    $table->string('name');
    $table->string('surname');
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending'); // pending, paid, failed, scanned
    $table->uuid('original_ticket_id')->nullable();
    $table->string('event_name')->nullable();
    $table->date('event_date')->nullable();
    $table->string('location')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('scanned_at')->nullable();
    $table->string('scanned_by')->nullable();
    $table->unsignedBigInteger('pg_order_id')->nullable();
    $table->text('pg_hpp_url')->nullable();
    $table->text('pg_password')->nullable(); // encrypted
    $table->text('qr_code')->nullable(); // encrypted
    $table->timestamp('failed_at')->nullable();
    $table->string('fail_reason')->nullable();
    $table->timestamps();

    $table->index('personal_number');
    $table->index('status');
    $table->index('pg_order_id');
});
```

Model `backend/app/Models/SoldTicket.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SoldTicket extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'personal_number', 'email', 'name', 'surname', 'amount',
        'status', 'original_ticket_id', 'event_name', 'event_date',
        'location', 'paid_at', 'scanned_at', 'scanned_by',
        'pg_order_id', 'pg_hpp_url', 'pg_password', 'qr_code',
        'failed_at', 'fail_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'event_date' => 'date',
            'paid_at' => 'datetime',
            'scanned_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function setPgPasswordAttribute(?string $value): void
    {
        $this->attributes['pg_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPgPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setQrCodeAttribute(?string $value): void
    {
        $this->attributes['qr_code'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getQrCodeAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
```

- [ ] **Step 7: Create Products, ProductSizes migrations and models**

Products migration:
```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->json('title');
    $table->json('description')->nullable();
    $table->decimal('price_gel', 10, 2);
    $table->string('category')->nullable();
    $table->boolean('is_vip')->default(false);
    $table->uuid('image_id')->nullable();
    $table->string('status')->default('draft');
    $table->timestamps();

    $table->foreign('image_id')->references('id')->on('media')->nullOnDelete();
});
```

ProductSizes migration:
```php
Schema::create('product_sizes', function (Blueprint $table) {
    $table->id();
    $table->uuid('product_id');
    $table->string('size');
    $table->integer('quantity')->default(0);
    $table->timestamps();

    $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
    $table->unique(['product_id', 'size']);
});
```

Models:
```php
// app/Models/Product.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'title', 'description', 'price_gel', 'category',
        'is_vip', 'image_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price_gel' => 'decimal:2',
            'is_vip' => 'boolean',
        ];
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

// app/Models/ProductSize.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    protected $fillable = ['product_id', 'size', 'quantity'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

- [ ] **Step 8: Create ProductOrders migration and model**

Migration:
```php
Schema::create('product_orders', function (Blueprint $table) {
    $table->string('id', 8)->primary();
    $table->uuid('product_id');
    $table->string('product_title');
    $table->string('size');
    $table->string('name');
    $table->string('email');
    $table->string('phone');
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending'); // pending, paid, collected, failed
    $table->unsignedBigInteger('pg_order_id')->nullable();
    $table->text('pg_password')->nullable(); // encrypted
    $table->text('qr_code')->nullable(); // encrypted
    $table->timestamps();

    $table->index('status');
    $table->index('pg_order_id');
});
```

Model `backend/app/Models/ProductOrder.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ProductOrder extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'product_id', 'product_title', 'size', 'name', 'email',
        'phone', 'amount', 'status', 'pg_order_id', 'pg_password', 'qr_code',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function setPgPasswordAttribute(?string $value): void
    {
        $this->attributes['pg_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPgPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setQrCodeAttribute(?string $value): void
    {
        $this->attributes['qr_code'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getQrCodeAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
```

- [ ] **Step 9: Create remaining migrations and models**

**JokerTickets:**
```php
// Migration
Schema::create('joker_tickets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('sold_ticket_id', 8);
    $table->string('personal_number', 11);
    $table->string('email');
    $table->string('name');
    $table->string('surname');
    $table->timestamps();

    $table->foreign('sold_ticket_id')->references('id')->on('sold_tickets');
});

// Model: app/Models/JokerTicket.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JokerTicket extends Model
{
    use HasUuids;

    protected $fillable = ['sold_ticket_id', 'personal_number', 'email', 'name', 'surname'];

    public function soldTicket(): BelongsTo
    {
        return $this->belongsTo(SoldTicket::class);
    }
}
```

**MusicTracks:**
```php
// Migration
Schema::create('music_tracks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->json('title');
    $table->string('artist');
    $table->uuid('audio_file_id')->nullable();
    $table->integer('order')->default(0);
    $table->string('status')->default('draft');
    $table->timestamps();

    $table->foreign('audio_file_id')->references('id')->on('media')->nullOnDelete();
});

// Model: app/Models/MusicTrack.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class MusicTrack extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title'];
    protected $fillable = ['title', 'artist', 'audio_file_id', 'order', 'status'];

    public function audioFile(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_file_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }
}
```

**Pages:**
```php
// Migration
Schema::create('pages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->json('title');
    $table->json('nav_label')->nullable();
    $table->string('slug')->unique();
    $table->string('route_path')->nullable();
    $table->boolean('show_in_nav')->default(false);
    $table->integer('nav_order')->default(0);
    $table->boolean('featured_on_home')->default(false);
    $table->string('layout')->nullable();
    $table->json('content_blocks')->nullable();
    $table->boolean('is_published')->default(false);
    $table->timestamps();
});

// Model: app/Models/Page.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasUuids, HasTranslations, LogsActivity;

    public array $translatable = ['title', 'nav_label'];

    protected $fillable = [
        'title', 'nav_label', 'slug', 'route_path', 'show_in_nav',
        'nav_order', 'featured_on_home', 'layout', 'content_blocks', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'show_in_nav' => 'boolean',
            'featured_on_home' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeInNav(Builder $query): Builder
    {
        return $query->where('show_in_nav', true)->orderBy('nav_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
```

**Posts:**
```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->json('title');
    $table->json('body')->nullable();
    $table->string('slug')->unique();
    $table->string('status')->default('draft');
    $table->boolean('featured')->default(false);
    $table->timestamps();
});

// Model: app/Models/Post.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title', 'body'];
    protected $fillable = ['title', 'body', 'slug', 'status', 'featured'];

    protected function casts(): array
    {
        return ['featured' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
```

**Partners:**
```php
// Migration
Schema::create('partners', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->json('description')->nullable();
    $table->uuid('logo_id')->nullable();
    $table->string('url')->nullable();
    $table->integer('order')->default(0);
    $table->timestamps();

    $table->foreign('logo_id')->references('id')->on('media')->nullOnDelete();
});

// Model: app/Models/Partner.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Partner extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['description'];
    protected $fillable = ['name', 'description', 'logo_id', 'url', 'order'];

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }
}
```

**SiteSettings:**
```php
// Migration
Schema::create('site_settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->json('value')->nullable();
    $table->timestamps();
});

// Model: app/Models/SiteSetting.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'key';
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::find($key)?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

- [ ] **Step 10: Run migrations and tests**

Run:
```bash
cd backend
php artisan migrate:fresh
php artisan test --filter=ModelTest
```
Expected: All 7 tests PASS. Check PgAdmin — all tables visible.

- [ ] **Step 11: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add all database migrations and Eloquent models with translatable, encrypted fields, and scopes"
```

---

## Task 3: Sanctum Auth + Security Middleware

**Files:**
- Modify: `backend/config/sanctum.php` — stateful domains
- Modify: `backend/config/cors.php` — strict origin
- Create: `backend/app/Http/Middleware/LocaleFromHeader.php`
- Create: `backend/app/Http/Middleware/ForceHttps.php`
- Modify: `backend/bootstrap/app.php` — register middleware
- Modify: `backend/routes/api.php` — auth routes
- Create: `backend/app/Http/Controllers/Admin/AuthController.php`
- Create: `backend/app/Http/Requests/LoginRequest.php`
- Test: `backend/tests/Feature/AuthTest.php`

**Interfaces:**
- Consumes: `User` model from Task 2
- Produces: `POST /api/admin/login` (returns Sanctum token), `POST /api/admin/logout`, `GET /api/admin/user`, `LocaleFromHeader` middleware sets app locale from `Accept-Language` header

- [ ] **Step 1: Write auth tests**

Create `backend/tests/Feature/AuthTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd backend && php artisan test --filter=AuthTest`
Expected: All tests FAIL.

- [ ] **Step 3: Configure Sanctum and CORS**

Edit `backend/config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000')),
```

Edit `backend/config/cors.php`:
```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
'supports_credentials' => true,
```

- [ ] **Step 4: Create LocaleFromHeader middleware**

```php
// app/Http/Middleware/LocaleFromHeader.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocaleFromHeader
{
    private const SUPPORTED = ['ka', 'en', 'ru', 'ua'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', 'ka');
        $locale = in_array($locale, self::SUPPORTED, true) ? $locale : 'ka';
        app()->setLocale($locale);

        return $next($request);
    }
}
```

- [ ] **Step 5: Create LoginRequest**

```php
// app/Http/Requests/LoginRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 6: Create AuthController**

```php
// app/Http/Controllers/Admin/AuthController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
```

- [ ] **Step 7: Create LocaleController**

```php
// app/Http/Controllers/Api/LocaleController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class LocaleController extends Controller
{
    public function show()
    {
        return response()->json(['locale' => app()->getLocale()]);
    }
}
```

- [ ] **Step 8: Register routes and middleware**

Edit `backend/routes/api.php`:
```php
<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Api\LocaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('locale')->group(function () {
    Route::get('/locale', [LocaleController::class, 'show']);

    // Admin auth
    Route::post('/admin/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});
```

Register middleware alias in `backend/bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'locale' => \App\Http\Middleware\LocaleFromHeader::class,
    ]);
    $middleware->statefulApi();
})
```

- [ ] **Step 9: Add rate limits**

Edit `backend/app/Providers/AppServiceProvider.php`, in `boot()`:
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function ($request) {
    return Limit::perMinute(60)->by($request->ip());
});

RateLimiter::for('orders', function ($request) {
    return Limit::perMinute(10)->by($request->ip());
});

RateLimiter::for('payments', function ($request) {
    return Limit::perMinute(30)->by($request->ip());
});
```

- [ ] **Step 10: Run tests**

Run: `cd backend && php artisan test --filter=AuthTest`
Expected: All 7 tests PASS.

- [ ] **Step 11: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add Sanctum auth, CORS, locale middleware, rate limiting, and admin auth endpoints"
```

---

## Task 4: Services Layer — Payment, QR, PDF, Email

**Files:**
- Create: `backend/app/Services/PaymentService.php`
- Create: `backend/app/Services/QrCodeService.php`
- Create: `backend/app/Services/PdfService.php`
- Create: `backend/app/Services/EmailService.php`
- Create: `backend/app/Services/TicketService.php`
- Create: `backend/app/Services/ProductService.php`
- Create: `backend/app/Services/SiteSettingService.php`
- Create: `backend/app/Http/Middleware/VerifyQuipuHmac.php`
- Test: `backend/tests/Unit/PaymentServiceTest.php`
- Test: `backend/tests/Unit/QrCodeServiceTest.php`
- Test: `backend/tests/Feature/TicketServiceTest.php`

**Interfaces:**
- Consumes: Models from Task 2
- Produces:
  - `PaymentService::createOrder(array $body): array` — calls Quipu PG
  - `PaymentService::getOrderDetails(int $orderId, string $password): array` — fetches PG status
  - `PaymentService::createCallbackHmac(string $internalId): string`
  - `PaymentService::verifyCallbackHmac(string $internalId, string $hmac): bool`
  - `PaymentService::createRedirectToken(int $pgOrderId, string $collection): string`
  - `PaymentService::verifyRedirectToken(string $token): ?array`
  - `QrCodeService::generate(string $data): string` — returns data URL
  - `QrCodeService::generateTicketData(string $ticketId, string $personalNumber, string $eventId): string`
  - `PdfService::generateTicketPdf(array $data): string` — returns PDF binary
  - `EmailService::sendTicketEmail(string $to, string $name, string $pdfContent, string $ticketId, ?string $eventName): void`
  - `TicketService::listActive(): Collection` — cached
  - `TicketService::find(string $id): ?Ticket`
  - `ProductService::listActive(): Collection` — cached
  - `SiteSettingService::all(): array` — cached

- [ ] **Step 1: Write PaymentService unit tests**

Create `backend/tests/Unit/PaymentServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    public function test_create_callback_hmac_produces_hex_string(): void
    {
        $hmac = $this->service->createCallbackHmac('TEST1234');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hmac);
    }

    public function test_verify_callback_hmac_returns_true_for_valid(): void
    {
        $hmac = $this->service->createCallbackHmac('TEST1234');
        $this->assertTrue($this->service->verifyCallbackHmac('TEST1234', $hmac));
    }

    public function test_verify_callback_hmac_returns_false_for_invalid(): void
    {
        $this->assertFalse($this->service->verifyCallbackHmac('TEST1234', 'invalid'));
    }

    public function test_create_and_verify_redirect_token(): void
    {
        $token = $this->service->createRedirectToken(12345, 'soldTickets');
        $result = $this->service->verifyRedirectToken($token);

        $this->assertNotNull($result);
        $this->assertEquals(12345, $result['pgOrderId']);
        $this->assertEquals('soldTickets', $result['collection']);
    }

    public function test_verify_redirect_token_returns_null_for_tampered(): void
    {
        $token = $this->service->createRedirectToken(12345, 'soldTickets');
        $this->assertNull($this->service->verifyRedirectToken($token . 'x'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd backend && php artisan test --filter=PaymentServiceTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement PaymentService**

```php
// app/Services/PaymentService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function createOrder(array $body): array
    {
        $response = Http::withOptions($this->tlsOptions())
            ->post(config('services.quipu.api_url'), array_merge($body, [
                'typeRid' => config('services.quipu.type_rid'),
            ]));

        $response->throw();

        return $response->json();
    }

    public function getOrderDetails(int $orderId, string $password): array
    {
        $url = config('services.quipu.api_url') . '/' . $orderId;

        $response = Http::withOptions($this->tlsOptions())
            ->withHeaders(['Authorization' => 'Basic ' . base64_encode($orderId . ':' . $password)])
            ->get($url);

        $response->throw();

        return $response->json();
    }

    public function createCallbackHmac(string $internalId): string
    {
        return hash_hmac('sha256', "callback:{$internalId}", $this->getSecret());
    }

    public function verifyCallbackHmac(string $internalId, string $hmac): bool
    {
        $expected = $this->createCallbackHmac($internalId);

        return hash_equals($expected, $hmac);
    }

    public function createRedirectToken(int $pgOrderId, string $collection): string
    {
        $data = base64_encode(json_encode([
            'pgOrderId' => $pgOrderId,
            'collection' => $collection,
        ]));

        $signature = hash_hmac('sha256', $data, $this->getSecret());

        return $data . '.' . $signature;
    }

    public function verifyRedirectToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$data, $signature] = $parts;
        $expected = hash_hmac('sha256', $data, $this->getSecret());

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode(base64_decode($data), true);

        if (!$decoded || !isset($decoded['pgOrderId'], $decoded['collection'])) {
            return null;
        }

        if (!in_array($decoded['collection'], ['soldTickets', 'productOrders'], true)) {
            return null;
        }

        return $decoded;
    }

    private function getSecret(): string
    {
        return config('app.key');
    }

    private function tlsOptions(): array
    {
        $options = [];

        $cert = config('services.quipu.cert_base64');
        $key = config('services.quipu.key_base64');
        $ca = config('services.quipu.ca_base64');

        if ($cert && $key) {
            $certPath = tempnam(sys_get_temp_dir(), 'pg_cert_');
            file_put_contents($certPath, base64_decode($cert));
            $keyPath = tempnam(sys_get_temp_dir(), 'pg_key_');
            file_put_contents($keyPath, base64_decode($key));

            $options['cert'] = $certPath;
            $options['ssl_key'] = $keyPath;

            if ($ca) {
                $caPath = tempnam(sys_get_temp_dir(), 'pg_ca_');
                file_put_contents($caPath, base64_decode($ca));
                $options['verify'] = $caPath;
            }
        }

        if (config('services.quipu.tls_reject_unauthorized') === false) {
            $options['verify'] = false;
        }

        return $options;
    }
}
```

Add to `backend/config/services.php`:
```php
'quipu' => [
    'api_url' => env('PG_API_URL'),
    'merchant_id' => env('PG_MERCHANT_ID'),
    'type_rid' => env('PG_TEST_TYPE_RID'),
    'cert_base64' => env('PG_CERT_BASE64'),
    'key_base64' => env('PG_KEY_BASE64'),
    'ca_base64' => env('PG_CA_BASE64'),
    'tls_reject_unauthorized' => env('PG_TLS_REJECT_UNAUTHORIZED', true),
],

'resend' => [
    'api_key' => env('RESEND_API_KEY'),
],
```

- [ ] **Step 4: Implement QrCodeService**

```php
// app/Services/QrCodeService.php
<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generate(string $data): string
    {
        $png = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($data);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function generateTicketData(string $ticketId, string $personalNumber, string $eventId): string
    {
        return json_encode([
            'ticketId' => $ticketId,
            'personalNumber' => $personalNumber,
            'eventId' => $eventId,
            'timestamp' => now()->toISOString(),
            'version' => 1,
        ]);
    }
}
```

- [ ] **Step 5: Implement PdfService**

```php
// app/Services/PdfService.php
<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateTicketPdf(array $data): string
    {
        $pdf = Pdf::loadView('pdf.ticket', $data)
            ->setPaper([0, 0, 650, 1000]);

        return $pdf->output();
    }
}
```

Create `backend/resources/views/pdf/ticket.blade.php` with the ticket design matching existing layout (black background, orange borders, QR code, event info).

- [ ] **Step 6: Implement TicketService and ProductService**

```php
// app/Services/TicketService.php
<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    public function listActive(): Collection
    {
        return Cache::remember('tickets:active', 3600, function () {
            return Ticket::active()->get();
        });
    }

    public function find(string $id): ?Ticket
    {
        return Ticket::find($id);
    }

    public function clearCache(): void
    {
        Cache::forget('tickets:active');
    }
}

// app/Services/ProductService.php
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function listActive(): Collection
    {
        return Cache::remember('products:active', 3600, function () {
            return Product::active()->with(['sizes', 'image'])->get();
        });
    }

    public function find(string $id): ?Product
    {
        return Product::with(['sizes', 'image'])->find($id);
    }

    public function clearCache(): void
    {
        Cache::forget('products:active');
    }
}

// app/Services/SiteSettingService.php
<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingService
{
    public function all(): array
    {
        return Cache::remember('site-settings', 3600, function () {
            return SiteSetting::all()->pluck('value', 'key')->toArray();
        });
    }

    public function clearCache(): void
    {
        Cache::forget('site-settings');
    }
}
```

- [ ] **Step 7: Implement EmailService**

```php
// app/Services/EmailService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\TicketPurchased;
use App\Mail\ProductOrderConfirmed;

class EmailService
{
    public function sendTicketEmail(
        string $to,
        string $name,
        string $pdfContent,
        string $ticketId,
        ?string $eventName = null
    ): void {
        Mail::to($to)->send(new TicketPurchased(
            name: $name,
            pdfContent: $pdfContent,
            ticketId: $ticketId,
            eventName: $eventName,
        ));
    }

    public function sendProductOrderEmail(
        string $to,
        string $name,
        string $qrPng,
        string $orderId,
        string $productTitle,
        string $size
    ): void {
        Mail::to($to)->send(new ProductOrderConfirmed(
            name: $name,
            qrPng: $qrPng,
            orderId: $orderId,
            productTitle: $productTitle,
            size: $size,
        ));
    }
}
```

Create corresponding Mailable classes `app/Mail/TicketPurchased.php` and `app/Mail/ProductOrderConfirmed.php` with Blade views.

- [ ] **Step 8: Create VerifyQuipuHmac middleware**

```php
// app/Http/Middleware/VerifyQuipuHmac.php
<?php

namespace App\Http\Middleware;

use App\Services\PaymentService;
use Closure;
use Illuminate\Http\Request;

class VerifyQuipuHmac
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $ref = $request->query('ref');
        $sig = $request->query('sig');

        if (!$ref || !$sig || !$this->paymentService->verifyCallbackHmac($ref, $sig)) {
            return response()->json(['error' => 'Invalid callback signature'], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 9: Run all tests**

Run: `cd backend && php artisan test`
Expected: All tests PASS.

- [ ] **Step 10: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add services layer — PaymentService, QrCode, PDF, Email, TicketService, ProductService, SiteSettingService, HMAC middleware"
```

---

## Task 5: Actions + Payment API Controllers

**Files:**
- Create: `backend/app/Actions/CreateTicketOrderAction.php`
- Create: `backend/app/Actions/CreateProductOrderAction.php`
- Create: `backend/app/Actions/ProcessPaymentCallbackAction.php`
- Create: `backend/app/Actions/ValidateTicketAction.php`
- Create: `backend/app/Actions/SendTicketEmailAction.php`
- Create: `backend/app/Http/Controllers/Payment/TicketOrderController.php`
- Create: `backend/app/Http/Controllers/Payment/ProductOrderController.php`
- Create: `backend/app/Http/Controllers/Payment/PaymentCallbackController.php`
- Create: `backend/app/Http/Controllers/Admin/TicketScannerController.php`
- Create: `backend/app/Http/Requests/CreateTicketOrderRequest.php`
- Create: `backend/app/Http/Requests/CreateProductOrderRequest.php`
- Create: `backend/app/Http/Requests/CheckPersonalNumberRequest.php`
- Create: `backend/app/Jobs/SendTicketEmailJob.php`
- Create: `backend/app/Jobs/SendProductOrderEmailJob.php`
- Modify: `backend/routes/api.php` — add payment + scanner routes
- Test: `backend/tests/Feature/TicketOrderTest.php`
- Test: `backend/tests/Feature/PaymentCallbackTest.php`
- Test: `backend/tests/Feature/TicketScannerTest.php`

**Interfaces:**
- Consumes: All Services from Task 4, Models from Task 2, Auth from Task 3
- Produces: Full payment flow API (`POST /api/orders/tickets`, `POST /api/orders/products`, `GET /api/payments/callback`, `GET /api/payments/redirect`, `POST /api/admin/validate-ticket`)

- [ ] **Step 1: Write TicketOrder feature test**

Create `backend/tests/Feature/TicketOrderTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ticket_order_validates_input(): void
    {
        $response = $this->postJson('/api/orders/tickets', []);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ticketId', 'name', 'surname', 'email', 'personalNumber']);
    }

    public function test_create_ticket_order_rejects_sold_out(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 50,
            'quantity' => 0,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/orders/tickets', [
            'ticketId' => $ticket->id,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'personalNumber' => '12345678901',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'sold_out');
    }

    public function test_personal_number_max_3_tickets(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 50,
            'quantity' => 100,
            'event_date' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
        ]);

        // Create 3 paid tickets for same personal number
        for ($i = 0; $i < 3; $i++) {
            \App\Models\SoldTicket::create([
                'id' => 'TST' . str_pad($i, 5, '0', STR_PAD_LEFT),
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

        $response = $this->postJson('/api/orders/tickets', [
            'ticketId' => $ticket->id,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'personalNumber' => '12345678901',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'max_tickets_reached');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd backend && php artisan test --filter=TicketOrderTest`
Expected: FAIL.

- [ ] **Step 3: Create FormRequests**

```php
// app/Http/Requests/CreateTicketOrderRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ticketId' => ['required', 'uuid', 'exists:tickets,id'],
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-]+$/'],
            'surname' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-]+$/'],
            'email' => ['required', 'email:rfc,dns'],
            'personalNumber' => ['required', 'string', 'digits:11'],
        ];
    }
}

// app/Http/Requests/CreateProductOrderRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'productId' => ['required', 'uuid', 'exists:products,id'],
            'size' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['required', 'string', 'min:9', 'regex:/^\+?[0-9]+$/'],
        ];
    }
}

// app/Http/Requests/CheckPersonalNumberRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckPersonalNumberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'personalNumber' => ['required', 'string', 'digits:11'],
        ];
    }
}
```

- [ ] **Step 4: Create CreateTicketOrderAction**

```php
// app/Actions/CreateTicketOrderAction.php
<?php

namespace App\Actions;

use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Services\QrCodeService;
use Illuminate\Support\Str;

class CreateTicketOrderAction
{
    public function __construct(
        private PaymentService $paymentService,
        private QrCodeService $qrCodeService,
    ) {}

    public function execute(array $data): array
    {
        $ticket = Ticket::findOrFail($data['ticketId']);

        // Check status and quantity
        if ($ticket->status !== 'active' || $ticket->quantity <= 0) {
            return ['error' => 'sold_out', 'status' => 400];
        }

        // Max 3 tickets per personal number
        $paidCount = SoldTicket::where('personal_number', $data['personalNumber'])
            ->where('status', 'paid')
            ->count();

        if ($paidCount >= 3) {
            return ['error' => 'max_tickets_reached', 'status' => 400];
        }

        // Generate internal ID
        $internalId = strtoupper(Str::random(8));

        // Create callback HMAC
        $hmac = $this->paymentService->createCallbackHmac($internalId);

        // Build PG order
        $appUrl = config('app.url');
        $callbackUrl = "{$appUrl}/api/payments/callback?ref={$internalId}&sig={$hmac}";
        $redirectUrl = "{$appUrl}/api/payments/redirect";

        $pgResponse = $this->paymentService->createOrder([
            'amount' => (int) ($ticket->price_gel * 100), // cents
            'description' => $ticket->setLocale('en')->title ?? $ticket->setLocale('ka')->title,
            'merchantId' => config('services.quipu.merchant_id'),
            'callbackUrl' => $callbackUrl,
            'redirectUrl' => $redirectUrl,
        ]);

        // Generate QR
        $qrData = $this->qrCodeService->generateTicketData(
            $internalId,
            $data['personalNumber'],
            $ticket->id,
        );

        // Store pending ticket
        SoldTicket::create([
            'id' => $internalId,
            'personal_number' => $data['personalNumber'],
            'email' => $data['email'],
            'name' => $data['name'],
            'surname' => $data['surname'],
            'amount' => $ticket->price_gel,
            'status' => 'pending',
            'original_ticket_id' => $ticket->id,
            'event_name' => $ticket->setLocale('ka')->title,
            'event_date' => $ticket->event_date,
            'location' => $ticket->location,
            'pg_order_id' => $pgResponse['orderId'],
            'pg_hpp_url' => $pgResponse['hppUrl'],
            'pg_password' => $pgResponse['password'],
            'qr_code' => $qrData,
        ]);

        // Create signed redirect token
        $token = $this->paymentService->createRedirectToken(
            $pgResponse['orderId'],
            'soldTickets',
        );

        return [
            'redirectUrl' => "/api/payments/redirect?token={$token}",
            'status' => 200,
        ];
    }
}
```

- [ ] **Step 5: Create ProcessPaymentCallbackAction**

```php
// app/Actions/ProcessPaymentCallbackAction.php
<?php

namespace App\Actions;

use App\Models\JokerTicket;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Jobs\SendTicketEmailJob;
use Illuminate\Support\Facades\DB;

class ProcessPaymentCallbackAction
{
    public function __construct(private PaymentService $paymentService) {}

    public function execute(string $ref, int $pgOrderId): array
    {
        $soldTicket = SoldTicket::where('id', $ref)
            ->where('pg_order_id', $pgOrderId)
            ->first();

        if (!$soldTicket) {
            return ['error' => 'ticket_not_found', 'status' => 404];
        }

        if ($soldTicket->status === 'paid') {
            return ['ticketId' => $ref, 'status' => 200];
        }

        // Check payment with Quipu
        $details = $this->paymentService->getOrderDetails(
            $soldTicket->pg_order_id,
            $soldTicket->pg_password,
        );

        $pgStatus = strtolower($details['status'] ?? '');
        $isPaid = in_array($pgStatus, ['paid', 'completed', 'fullypaid'], true);

        if (!$isPaid) {
            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'payment_' . $pgStatus,
            ]);
            return ['error' => 'payment_failed', 'status' => 400];
        }

        // Atomic inventory decrement
        $decremented = DB::table('tickets')
            ->where('id', $soldTicket->original_ticket_id)
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->decrement('quantity');

        if ($decremented === 0) {
            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'sold_out',
            ]);
            return ['error' => 'sold_out', 'status' => 400];
        }

        // Check if last unit — mark sold_out
        $ticket = Ticket::find($soldTicket->original_ticket_id);
        if ($ticket && $ticket->quantity <= 0) {
            $ticket->update(['status' => 'sold_out']);
        }

        // Mark paid
        $soldTicket->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Joker ticket handling
        if ($this->isJokerTicket($soldTicket->event_name)) {
            JokerTicket::create([
                'sold_ticket_id' => $soldTicket->id,
                'personal_number' => $soldTicket->personal_number,
                'email' => $soldTicket->email,
                'name' => $soldTicket->name,
                'surname' => $soldTicket->surname,
            ]);
        }

        // Queue email
        SendTicketEmailJob::dispatch($soldTicket->id);

        return ['ticketId' => $ref, 'status' => 200];
    }

    private function isJokerTicket(?string $eventName): bool
    {
        if (!$eventName) return false;
        return str_contains(strtolower($eventName), 'joker');
    }
}
```

- [ ] **Step 6: Create ValidateTicketAction**

```php
// app/Actions/ValidateTicketAction.php
<?php

namespace App\Actions;

use App\Models\SoldTicket;
use Illuminate\Support\Facades\DB;

class ValidateTicketAction
{
    public function execute(array $qrData): array
    {
        $ticketId = $qrData['ticketId'] ?? null;
        $personalNumber = $qrData['personalNumber'] ?? null;

        if (!$ticketId || !$personalNumber) {
            return ['error' => 'invalid_qr_data', 'status' => 400];
        }

        $soldTicket = SoldTicket::find($ticketId);

        if (!$soldTicket) {
            return ['error' => 'ticket_not_found', 'status' => 404];
        }

        if ($soldTicket->personal_number !== $personalNumber) {
            return ['error' => 'personal_number_mismatch', 'status' => 400];
        }

        if ($soldTicket->status !== 'paid') {
            return ['error' => 'ticket_not_paid', 'status' => 400];
        }

        if ($soldTicket->scanned_at) {
            return ['error' => 'already_scanned', 'scannedAt' => $soldTicket->scanned_at, 'status' => 409];
        }

        // Atomic scan
        $updated = DB::table('sold_tickets')
            ->where('id', $ticketId)
            ->whereNull('scanned_at')
            ->update([
                'scanned_at' => now(),
                'scanned_by' => 'admin',
                'status' => 'scanned',
            ]);

        if ($updated === 0) {
            return ['error' => 'already_scanned', 'status' => 409];
        }

        $soldTicket->refresh();

        return [
            'ticket' => [
                'id' => $soldTicket->id,
                'name' => $soldTicket->name,
                'surname' => $soldTicket->surname,
                'personalNumber' => $soldTicket->personal_number,
                'eventName' => $soldTicket->event_name,
                'eventDate' => $soldTicket->event_date,
                'amount' => $soldTicket->amount,
                'paidAt' => $soldTicket->paid_at,
            ],
            'status' => 200,
        ];
    }
}
```

- [ ] **Step 7: Create Controllers**

```php
// app/Http/Controllers/Payment/TicketOrderController.php
<?php

namespace App\Http\Controllers\Payment;

use App\Actions\CreateTicketOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketOrderRequest;

class TicketOrderController extends Controller
{
    public function store(CreateTicketOrderRequest $request, CreateTicketOrderAction $action)
    {
        $result = $action->execute($request->validated());
        return response()->json($result, $result['status']);
    }
}

// app/Http/Controllers/Payment/PaymentCallbackController.php
<?php

namespace App\Http\Controllers\Payment;

use App\Actions\ProcessPaymentCallbackAction;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request, ProcessPaymentCallbackAction $action)
    {
        $ref = $request->query('ref');
        $pgOrderId = (int) $request->query('ID');

        $result = $action->execute($ref, $pgOrderId);

        if (isset($result['error'])) {
            $frontendUrl = config('app.frontend_url');
            return redirect("{$frontendUrl}/dashboard/fail?error={$result['error']}");
        }

        $frontendUrl = config('app.frontend_url');
        return redirect("{$frontendUrl}/dashboard/success?ticketId={$result['ticketId']}");
    }

    public function redirect(Request $request, PaymentService $paymentService)
    {
        $token = $request->query('token');
        $data = $paymentService->verifyRedirectToken($token);

        if (!$data) {
            $frontendUrl = config('app.frontend_url');
            return redirect("{$frontendUrl}/dashboard/fail?error=invalid_token");
        }

        // Redirect to PG HPP URL based on collection
        // The actual HPP redirect is handled by the stored pg_hpp_url
        $frontendUrl = config('app.frontend_url');
        return redirect("{$frontendUrl}/dashboard/success");
    }
}

// app/Http/Controllers/Admin/TicketScannerController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ValidateTicketAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketScannerController extends Controller
{
    public function validate(Request $request, ValidateTicketAction $action): JsonResponse
    {
        $result = $action->execute($request->all());
        return response()->json($result, $result['status']);
    }
}
```

- [ ] **Step 8: Create Jobs**

```php
// app/Jobs/SendTicketEmailJob.php
<?php

namespace App\Jobs;

use App\Models\SoldTicket;
use App\Services\EmailService;
use App\Services\PdfService;
use App\Services\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTicketEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public string $soldTicketId) {}

    public function handle(EmailService $emailService, PdfService $pdfService): void
    {
        $soldTicket = SoldTicket::findOrFail($this->soldTicketId);

        $pdfContent = $pdfService->generateTicketPdf([
            'id' => $soldTicket->id,
            'name' => $soldTicket->name,
            'surname' => $soldTicket->surname,
            'personalNumber' => $soldTicket->personal_number,
            'eventName' => $soldTicket->event_name,
            'eventDate' => $soldTicket->event_date,
            'amount' => $soldTicket->amount,
            'currency' => 'GEL',
            'qrCodeDataUrl' => (new QrCodeService())->generate($soldTicket->qr_code),
        ]);

        $emailService->sendTicketEmail(
            to: $soldTicket->email,
            name: $soldTicket->name,
            pdfContent: $pdfContent,
            ticketId: $soldTicket->id,
            eventName: $soldTicket->event_name,
        );
    }
}
```

- [ ] **Step 9: Add payment routes**

Add to `backend/routes/api.php`:
```php
use App\Http\Controllers\Payment\TicketOrderController;
use App\Http\Controllers\Payment\ProductOrderController;
use App\Http\Controllers\Payment\PaymentCallbackController;
use App\Http\Controllers\Admin\TicketScannerController;
use App\Http\Controllers\Api\PersonalNumberController;

Route::middleware(['locale', 'throttle:orders'])->group(function () {
    Route::post('/orders/tickets', [TicketOrderController::class, 'store']);
    Route::post('/orders/products', [ProductOrderController::class, 'store']);
    Route::post('/check-personal-number', [PersonalNumberController::class, 'check']);
});

Route::middleware('throttle:payments')->group(function () {
    Route::get('/payments/callback', [PaymentCallbackController::class, 'handle'])
        ->middleware('quipu.hmac');
    Route::get('/payments/redirect', [PaymentCallbackController::class, 'redirect']);
});

// Inside auth:sanctum group:
Route::post('/admin/validate-ticket', [TicketScannerController::class, 'validate']);
```

Register middleware alias:
```php
'quipu.hmac' => \App\Http\Middleware\VerifyQuipuHmac::class,
```

- [ ] **Step 10: Run tests**

Run: `cd backend && php artisan test`
Expected: All tests PASS.

- [ ] **Step 11: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add Actions, payment controllers, Jobs, and full payment flow API"
```

---

## Task 6: Public API Controllers

**Files:**
- Create: `backend/app/Http/Controllers/Api/TicketController.php`
- Create: `backend/app/Http/Controllers/Api/ProductController.php`
- Create: `backend/app/Http/Controllers/Api/MusicTrackController.php`
- Create: `backend/app/Http/Controllers/Api/PageController.php`
- Create: `backend/app/Http/Controllers/Api/PostController.php`
- Create: `backend/app/Http/Controllers/Api/PartnerController.php`
- Create: `backend/app/Http/Controllers/Api/SiteSettingController.php`
- Create: `backend/app/Http/Controllers/Api/PersonalNumberController.php`
- Modify: `backend/routes/api.php` — add public routes
- Test: `backend/tests/Feature/PublicApiTest.php`

**Interfaces:**
- Consumes: TicketService, ProductService, SiteSettingService from Task 4, Models from Task 2
- Produces: All public GET endpoints (`/api/tickets`, `/api/products`, `/api/music-tracks`, `/api/pages/{slug}`, `/api/posts`, `/api/partners`, `/api/site-settings`)

- [ ] **Step 1: Write public API tests**

Create `backend/tests/Feature/PublicApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\Product;
use App\Models\MusicTrack;
use App\Models\Page;
use App\Models\Post;
use App\Models\Partner;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_active_tickets(): void
    {
        Ticket::create([
            'title' => ['ka' => 'Active', 'en' => 'Active'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'active',
        ]);
        Ticket::create([
            'title' => ['ka' => 'Draft'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'draft',
        ]);

        $response = $this->getJson('/api/tickets');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_get_single_ticket(): void
    {
        $ticket = Ticket::create([
            'title' => ['ka' => 'Test', 'en' => 'Test EN'],
            'price_gel' => 50, 'quantity' => 10,
            'event_date' => '2026-08-01', 'location' => 'Tbilisi', 'status' => 'active',
        ]);

        $response = $this->getJson("/api/tickets/{$ticket->id}", ['Accept-Language' => 'en']);
        $response->assertOk()->assertJsonPath('data.title', 'Test EN');
    }

    public function test_list_products_with_sizes(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'Shirt'], 'price_gel' => 30, 'status' => 'active',
        ]);
        $product->sizes()->create(['size' => 'M', 'quantity' => 5]);

        $response = $this->getJson('/api/products');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sizes.0.size', 'M');
    }

    public function test_get_page_by_slug(): void
    {
        Page::create([
            'title' => ['ka' => 'About'], 'slug' => 'about', 'is_published' => true,
        ]);

        $response = $this->getJson('/api/pages/about');
        $response->assertOk()->assertJsonPath('data.slug', 'about');
    }

    public function test_get_unpublished_page_returns_404(): void
    {
        Page::create([
            'title' => ['ka' => 'Draft'], 'slug' => 'draft', 'is_published' => false,
        ]);

        $this->getJson('/api/pages/draft')->assertNotFound();
    }

    public function test_list_music_tracks_ordered(): void
    {
        MusicTrack::create(['title' => ['ka' => 'B'], 'artist' => 'X', 'order' => 2, 'status' => 'active']);
        MusicTrack::create(['title' => ['ka' => 'A'], 'artist' => 'Y', 'order' => 1, 'status' => 'active']);

        $response = $this->getJson('/api/music-tracks');
        $response->assertOk()->assertJsonPath('data.0.title', 'A');
    }

    public function test_site_settings(): void
    {
        SiteSetting::set('heroTitle', ['ka' => 'გამარჯობა', 'en' => 'Hello']);

        $response = $this->getJson('/api/site-settings');
        $response->assertOk()->assertJsonPath('data.heroTitle.ka', 'გამარჯობა');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd backend && php artisan test --filter=PublicApiTest`
Expected: FAIL.

- [ ] **Step 3: Implement controllers**

```php
// app/Http/Controllers/Api/TicketController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TicketService;

class TicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function index()
    {
        return response()->json(['data' => $this->ticketService->listActive()]);
    }

    public function show(string $id)
    {
        $ticket = $this->ticketService->find($id);
        if (!$ticket) {
            return response()->json(['error' => 'not_found'], 404);
        }
        return response()->json(['data' => $ticket]);
    }
}

// app/Http/Controllers/Api/ProductController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index()
    {
        return response()->json(['data' => $this->productService->listActive()]);
    }

    public function show(string $id)
    {
        $product = $this->productService->find($id);
        if (!$product) {
            return response()->json(['error' => 'not_found'], 404);
        }
        return response()->json(['data' => $product]);
    }
}

// app/Http/Controllers/Api/MusicTrackController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MusicTrack;
use Illuminate\Support\Facades\Cache;

class MusicTrackController extends Controller
{
    public function index()
    {
        $tracks = Cache::remember('music-tracks', 3600, function () {
            return MusicTrack::active()->ordered()->with('audioFile')->get();
        });
        return response()->json(['data' => $tracks]);
    }
}

// app/Http/Controllers/Api/PageController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->first();
        if (!$page) {
            return response()->json(['error' => 'not_found'], 404);
        }
        return response()->json(['data' => $page]);
    }
}

// app/Http/Controllers/Api/PostController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Post::published()->latest()->get()]);
    }
}

// app/Http/Controllers/Api/PartnerController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;

class PartnerController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Partner::orderBy('order')->with('logo')->get()]);
    }
}

// app/Http/Controllers/Api/SiteSettingController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;

class SiteSettingController extends Controller
{
    public function __construct(private SiteSettingService $siteSettingService) {}

    public function index()
    {
        return response()->json(['data' => $this->siteSettingService->all()]);
    }
}

// app/Http/Controllers/Api/PersonalNumberController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckPersonalNumberRequest;
use App\Models\SoldTicket;

class PersonalNumberController extends Controller
{
    public function check(CheckPersonalNumberRequest $request)
    {
        $count = SoldTicket::where('personal_number', $request->personalNumber)
            ->where('status', 'paid')
            ->count();

        return response()->json([
            'personalNumber' => $request->personalNumber,
            'ticketCount' => $count,
            'canPurchase' => $count < 3,
        ]);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `backend/routes/api.php`:
```php
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\MusicTrackController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\SiteSettingController;

Route::middleware('locale')->group(function () {
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/music-tracks', [MusicTrackController::class, 'index']);
    Route::get('/pages/{slug}', [PageController::class, 'show']);
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/partners', [PartnerController::class, 'index']);
    Route::get('/site-settings', [SiteSettingController::class, 'index']);
});
```

- [ ] **Step 5: Run tests**

Run: `cd backend && php artisan test`
Expected: All tests PASS.

- [ ] **Step 6: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add public API controllers for tickets, products, music tracks, pages, posts, partners, site settings"
```

---

## Task 7: Filament Admin Panel

**Files:**
- Create: `backend/app/Filament/Resources/TicketResource.php` (+ all Resources)
- Create: `backend/app/Filament/Pages/Dashboard.php`
- Create: `backend/app/Filament/Pages/TicketScanner.php`
- Create: `backend/app/Filament/Pages/ActivityLog.php`
- Create: `backend/app/Filament/Widgets/RevenueChart.php`
- Create: `backend/app/Filament/Widgets/SalesBreakdown.php`
- Create: `backend/app/Filament/Widgets/InventoryAlerts.php`
- Modify: `backend/app/Providers/Filament/AdminPanelProvider.php`

**Interfaces:**
- Consumes: All Models from Task 2, Services from Task 4
- Produces: Full Filament admin panel at `/admin` with CRUD resources, dashboard, QR scanner, activity log. Cache invalidation on save/delete for tickets, products, music tracks, site settings.

- [ ] **Step 1: Generate Filament resources**

Run:
```bash
cd backend
php artisan make:filament-resource Ticket --generate
php artisan make:filament-resource SoldTicket --generate
php artisan make:filament-resource Product --generate
php artisan make:filament-resource ProductOrder --generate
php artisan make:filament-resource MusicTrack --generate
php artisan make:filament-resource Page --generate
php artisan make:filament-resource Post --generate
php artisan make:filament-resource Partner --generate
php artisan make:filament-resource Media --generate
```

- [ ] **Step 2: Customize TicketResource with translatable tabs**

Edit `backend/app/Filament/Resources/TicketResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class TicketResource extends Resource
{
    use Translatable;

    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Shop';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('price_gel')->numeric()->required(),
            Forms\Components\TextInput::make('quantity')->numeric()->required(),
            Forms\Components\DatePicker::make('event_date')->required(),
            Forms\Components\TextInput::make('location')->required(),
            Forms\Components\Select::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out'])
                ->required(),
            Forms\Components\TextInput::make('sale_url')->url(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('price_gel')->money('GEL'),
            Tables\Columns\TextColumn::make('quantity'),
            Tables\Columns\TextColumn::make('event_date')->date(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors(['warning' => 'draft', 'success' => 'active', 'danger' => 'sold_out']),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out']),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function afterSave(): void
    {
        Cache::forget('tickets:active');
    }

    public static function afterDelete(): void
    {
        Cache::forget('tickets:active');
    }
}
```

- [ ] **Step 3: Customize SoldTicketResource (read-only)**

Make SoldTicketResource read-only: remove Create page, disable edit/delete actions, add filters for status, date range, search by name/personalNumber/email.

- [ ] **Step 4: Customize ProductResource with sizes repeater**

Add `Forms\Components\Repeater` for sizes with `size` (TextInput) + `quantity` (TextInput numeric) fields. Add cache invalidation hooks.

- [ ] **Step 5: Create remaining Resources**

Customize each resource with appropriate form fields, table columns, and filters following the same pattern. Add Translatable trait to resources with multilingual models. Add cache invalidation to MusicTrack, SiteSetting resources.

- [ ] **Step 6: Create Dashboard page with widgets**

Create `backend/app/Filament/Pages/Dashboard.php` with custom widgets:

```php
// app/Filament/Widgets/RevenueChart.php — ApexCharts showing daily revenue
// app/Filament/Widgets/SalesBreakdown.php — ticket vs merch breakdown
// app/Filament/Widgets/InventoryAlerts.php — low-stock warnings
```

Data sources: `SoldTicket::where('status', 'paid')`, `ProductOrder::where('status', 'paid')`, `Ticket::where('quantity', '<', 5)`.

- [ ] **Step 7: Create TicketScanner custom page**

Create `backend/app/Filament/Pages/TicketScanner.php` — custom Filament page with `html5-qrcode` JavaScript integration via `@vite` or CDN. On scan, POST to `/api/admin/validate-ticket` with Sanctum auth.

- [ ] **Step 8: Create ActivityLog custom page**

Create `backend/app/Filament/Pages/ActivityLog.php` — Filament Table page combining SoldTickets + ProductOrders, sorted by newest first, with status badges and date filters.

- [ ] **Step 9: Configure AdminPanelProvider**

Edit `backend/app/Providers/Filament/AdminPanelProvider.php`:
```php
->path('admin')
->login()
->colors(['primary' => Color::Orange])
->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
->plugins([
    SpatieLaravelTranslatablePlugin::make()->defaultLocales(['ka', 'en', 'ru', 'ua']),
])
```

- [ ] **Step 10: Create admin user seeder**

```php
// database/seeders/AdminSeeder.php
User::create([
    'name' => 'Admin',
    'email' => 'admin@tbilisistyle.ge',
    'password' => bcrypt('changeme'),
    'role' => 'admin',
]);
```

Run: `cd backend && php artisan db:seed --class=AdminSeeder`

- [ ] **Step 11: Test admin panel**

Run: `cd backend && php artisan serve`
Open `http://localhost:8000/admin`, login with `admin@tbilisistyle.ge` / `changeme`.
Verify: all Resources visible, CRUD works, translatable tabs switch languages, dashboard widgets load.

- [ ] **Step 12: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add Filament admin panel with all Resources, Dashboard, TicketScanner, ActivityLog, and widgets"
```

---

## Task 8: Data Migration Command

**Files:**
- Create: `backend/app/Actions/MigratePayloadDataAction.php`
- Create: `backend/app/Console/Commands/MigrateFromPayload.php`
- Test: `backend/tests/Feature/MigratePayloadDataTest.php`

**Interfaces:**
- Consumes: All Models from Task 2
- Produces: Artisan command `php artisan migrate:from-payload --source-dsn=...` that reads Payload PostgreSQL tables and seeds Laravel tables

- [ ] **Step 1: Write migration test**

Test that given a source database with Payload-shaped data, the command correctly transforms and inserts into Laravel tables. Use SQLite in-memory as mock source.

- [ ] **Step 2: Implement MigrateFromPayload command**

```php
// app/Console/Commands/MigrateFromPayload.php
<?php

namespace App\Console\Commands;

use App\Actions\MigratePayloadDataAction;
use Illuminate\Console\Command;

class MigrateFromPayload extends Command
{
    protected $signature = 'migrate:from-payload {--source-dsn= : Source PostgreSQL DSN}';
    protected $description = 'One-time migration from Payload CMS PostgreSQL to Laravel schema';

    public function handle(MigratePayloadDataAction $action): int
    {
        $dsn = $this->option('source-dsn');
        if (!$dsn) {
            $this->error('--source-dsn is required');
            return 1;
        }

        $this->info('Starting Payload → Laravel data migration...');
        $action->execute($dsn, $this->output);
        $this->info('Migration complete.');

        return 0;
    }
}
```

- [ ] **Step 3: Implement MigratePayloadDataAction**

Maps Payload collection tables to Laravel models. Handles field name transformations (camelCase → snake_case), JSON locale fields, encrypted fields re-encryption.

- [ ] **Step 4: Run test and verify**

Run: `cd backend && php artisan test --filter=MigratePayloadData`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd backend
git add -A
git commit -m "feat: add one-time Payload-to-Laravel data migration command"
```

---

## Task 9: Next.js Frontend Migration

**Files:**
- Create: `frontend/` directory (copy from existing `app/(frontend)/`)
- Create: `frontend/lib/api.ts` — Laravel API client
- Modify: All data-fetching functions to use `api.ts` instead of Payload
- Remove: Payload CMS dependencies from frontend
- Create: `frontend/Dockerfile`
- Create: `frontend/.env.example`

**Interfaces:**
- Consumes: All Laravel API endpoints from Tasks 5-6
- Produces: Standalone Next.js frontend that fetches from Laravel API

- [ ] **Step 1: Copy frontend files**

```bash
mkdir -p frontend
# Copy app/(frontend)/ contents to frontend/app/
# Copy components/, lib/, i18n/, public/, styles/
# Copy package.json, tsconfig.json, next.config.ts, tailwind config, postcss config
```

- [ ] **Step 2: Create API client**

Create `frontend/lib/api.ts`:

```typescript
export class ApiError extends Error {
  constructor(public status: number, public body: unknown) {
    super(`API error ${status}`);
  }
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function api<T>(
  path: string,
  options?: RequestInit & { locale?: string }
): Promise<T> {
  const { locale, ...fetchOptions } = options || {};

  const res = await fetch(`${API_URL}${path}`, {
    ...fetchOptions,
    headers: {
      'Accept': 'application/json',
      'Accept-Language': locale || 'ka',
      ...fetchOptions?.headers,
    },
    credentials: 'include',
  });

  if (!res.ok) {
    throw new ApiError(res.status, await res.json().catch(() => null));
  }

  return res.json();
}
```

- [ ] **Step 3: Replace data fetching in lib/tickets.ts**

```typescript
// frontend/lib/tickets.ts
import { api } from './api';

export async function listTickets(locale: string) {
  const res = await api<{ data: Ticket[] }>('/api/tickets', { locale });
  return res.data;
}

export async function getTicket(id: string, locale: string) {
  const res = await api<{ data: Ticket }>(`/api/tickets/${id}`, { locale });
  return res.data;
}
```

Repeat for `products.ts`, `music-tracks.ts`, `pages.ts`, etc.

- [ ] **Step 4: Update image URLs**

Replace Vercel Blob URLs with `/storage/media/{filename}` pattern. Update components that render images.

- [ ] **Step 5: Remove Payload dependencies**

Remove from `frontend/package.json`:
- `@payloadcms/*`
- `payload`
- Any Payload-specific imports

Keep: `next`, `react`, `next-intl`, `tailwindcss`, all UI dependencies.

- [ ] **Step 6: Update payment form submissions**

Update forms to POST to Laravel endpoints:
- `/api/orders/tickets` instead of `/api/create-order`
- `/api/orders/products` instead of `/api/create-product-order`

- [ ] **Step 7: Create .env.example**

```
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

- [ ] **Step 8: Create Dockerfile**

```dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM node:20-alpine AS runner
WORKDIR /app
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public
EXPOSE 3000
CMD ["node", "server.js"]
```

- [ ] **Step 9: Test frontend locally**

```bash
cd frontend
npm install
npm run dev
```

Open `http://localhost:3000`, verify:
- Homepage loads with data from Laravel API
- Ticket listing works
- Product listing works with sizes
- i18n language switching works
- Navigation menu renders

- [ ] **Step 10: Commit**

```bash
git add frontend/
git commit -m "feat: create standalone Next.js frontend consuming Laravel API"
```

---

## Task 10: Docker Compose Production + Nginx

**Files:**
- Create: `docker-compose.yml` (production)
- Create: `nginx/conf.d/default.conf`
- Create: `backend/Dockerfile`
- Modify: `frontend/Dockerfile` (from Task 9)

**Interfaces:**
- Consumes: Laravel app (Task 1-8), Next.js frontend (Task 9)
- Produces: Production-ready Docker Compose with Nginx reverse proxy, SSL (Certbot), all services

- [ ] **Step 1: Create Laravel Dockerfile**

```dockerfile
# backend/Dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql gd opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

- [ ] **Step 2: Create Nginx config**

```nginx
# nginx/conf.d/default.conf
upstream laravel {
    server laravel:8000;
}

upstream nextjs {
    server nextjs:3000;
}

server {
    listen 80;
    server_name _;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Laravel API
    location /api/ {
        proxy_pass http://laravel;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Filament admin
    location /admin {
        proxy_pass http://laravel;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Sanctum
    location /sanctum/ {
        proxy_pass http://laravel;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Media storage
    location /storage/ {
        alias /var/www/html/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # PgAdmin (IP restricted)
    location /pgadmin/ {
        # allow YOUR_IP;
        # deny all;
        proxy_pass http://pgadmin:5050/;
        proxy_set_header Host $host;
        proxy_set_header X-Script-Name /pgadmin;
    }

    # Next.js frontend (catch-all)
    location / {
        proxy_pass http://nextjs;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

- [ ] **Step 3: Create production docker-compose.yml**

```yaml
services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - certbot-etc:/etc/letsencrypt:ro
      - laravel-storage:/var/www/html/storage/app/public:ro
    depends_on:
      - laravel
      - nextjs
    restart: unless-stopped

  laravel:
    build: ./backend
    expose:
      - "8000"
    env_file: ./backend/.env
    volumes:
      - laravel-storage:/var/www/html/storage/app/public
    depends_on:
      postgres:
        condition: service_healthy
    restart: unless-stopped

  nextjs:
    build: ./frontend
    expose:
      - "3000"
    env_file: ./frontend/.env
    depends_on:
      - laravel
    restart: unless-stopped

  postgres:
    image: postgres:16-alpine
    volumes:
      - pgdata:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
      interval: 5s
      timeout: 3s
      retries: 5
    restart: unless-stopped

  pgadmin:
    image: dpage/pgadmin4
    environment:
      PGADMIN_DEFAULT_EMAIL: ${PGADMIN_EMAIL}
      PGADMIN_DEFAULT_PASSWORD: ${PGADMIN_PASSWORD}
    expose:
      - "5050"
    depends_on:
      - postgres
    restart: unless-stopped

  certbot:
    image: certbot/certbot
    volumes:
      - certbot-etc:/etc/letsencrypt
    entrypoint: "/bin/sh -c 'trap exit TERM; while :; do sleep 12h; certbot renew; done'"

volumes:
  pgdata:
  laravel-storage:
  certbot-etc:
```

- [ ] **Step 4: Test local Docker build**

```bash
docker compose build
docker compose up -d
```

Verify all services start and Nginx routes correctly.

- [ ] **Step 5: Commit**

```bash
git add docker-compose.yml nginx/ backend/Dockerfile frontend/Dockerfile
git commit -m "feat: add production Docker Compose with Nginx reverse proxy, SSL, and all services"
```

---

## Task 11: End-to-End Testing + Final Verification

**Files:**
- Test: Full payment flow (create order → callback → email)
- Test: Admin panel CRUD operations
- Test: QR scanner flow
- Test: Frontend data rendering

**Interfaces:**
- Consumes: Everything from Tasks 1-10
- Produces: Verified working system

- [ ] **Step 1: Run full test suite**

```bash
cd backend && php artisan test
```
Expected: All tests PASS.

- [ ] **Step 2: Test payment flow manually**

1. Start dev environment: `docker compose -f docker-compose.dev.yml up -d`
2. Start Laravel: `cd backend && php artisan serve`
3. Start Next.js: `cd frontend && npm run dev`
4. Create a test ticket via Filament admin
5. Attempt to purchase via frontend
6. Verify Quipu redirect works (test gateway)
7. Check PgAdmin — sold_tickets table has pending record

- [ ] **Step 3: Test admin panel**

1. Login at `http://localhost:8000/admin`
2. Create/edit/delete tickets, products, music tracks, pages, posts, partners
3. Verify translatable tabs work (ka/en/ru/ua)
4. Check dashboard widgets load data
5. Test QR scanner page (use phone camera or mock QR)

- [ ] **Step 4: Test PgAdmin**

1. Open `http://localhost:5050`
2. Verify all tables visible in tbilisistyle database
3. Run a query: `SELECT * FROM tickets;`
4. Verify data matches what was created in Filament

- [ ] **Step 5: Test email flow**

1. Trigger a test email (or use tinker to dispatch SendTicketEmailJob)
2. Open Mailpit at `http://localhost:8025`
3. Verify email received with PDF attachment

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "chore: end-to-end verification complete — all flows tested"
```
