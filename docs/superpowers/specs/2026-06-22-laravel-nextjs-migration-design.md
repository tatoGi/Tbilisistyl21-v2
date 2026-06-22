# TbilisiStyle21: Laravel + Next.js Migration Design

**Date:** 2026-06-22
**Status:** Approved
**Approach:** Clean Split — Laravel API Backend + Next.js SSR Frontend

---

## 1. Overview

Migrate TbilisiStyle21 from full-stack Next.js + Payload CMS to a **Laravel API backend + Next.js frontend** architecture. The existing React frontend components are preserved with minimal changes (data fetching layer only). Laravel replaces Payload CMS entirely — API, admin panel (Filament), auth (Sanctum), payment processing, email queue, and all business logic.

### Goals

- Full backend control via Laravel (middleware, policies, gates, encryption)
- Production-grade security stack
- Filament admin panel replacing Payload CMS admin
- PgAdmin available both locally and on VPS
- Preserve existing frontend (components, i18n, styles)
- Single VPS deployment via Docker Compose

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────┐
│                      VPS (Docker Compose)           │
│                                                     │
│  ┌──────────┐    ┌──────────┐    ┌──────────────┐   │
│  │  Nginx   │───>│ Next.js  │    │   Laravel    │   │
│  │ (reverse │───>│  :3000   │    │    :8000     │   │
│  │  proxy)  │    │ frontend │───>│  API + Auth  │   │
│  └──────────┘    └──────────┘    │  + Filament  │   │
│       │                          └──────┬───────┘   │
│       │                                 │           │
│       │          ┌──────────────────────┘           │
│       │          v                                  │
│       │    ┌──────────┐    ┌──────────────┐         │
│       │    │PostgreSQL│    │   PgAdmin    │         │
│       │    │  :5432   │    │   :5050      │         │
│       │    └──────────┘    └──────────────┘         │
│       │                                             │
│       └─── SSL (Let's Encrypt / Certbot)            │
└─────────────────────────────────────────────────────┘
```

### Routing (Nginx)

- `/api/*` → Laravel :8000
- `/admin/*` → Laravel Filament :8000
- `/sanctum/*` → Laravel Sanctum :8000
- Everything else → Next.js :3000
- `/pgadmin/*` → PgAdmin :5050 (IP-restricted + HTTP Basic Auth)

### Local Development

```
Docker Compose (dev profile):
  - PostgreSQL :5432
  - PgAdmin :5050 (localhost, credentials in .env)
  - Mailpit :8025 (email testing)

Laravel: php artisan serve :8000
Next.js: npm run dev :3000
```

PgAdmin locally connects to PostgreSQL at `localhost:5432`. Developer sees the full database — tables, data, queries, indexes — from browser at `localhost:5050`.

---

## 3. Database Schema

PostgreSQL managed by **Laravel Migrations**. UUID primary keys preserved from Payload.

### Models & Tables

| Model | Table | Key Fields | Notes |
|---|---|---|---|
| User | `users` | email, password, name, role (admin/editor) | Sanctum auth |
| Ticket | `tickets` | title (json), description (json), priceGel, quantity, eventDate, location, status | Translatable |
| SoldTicket | `sold_tickets` | personalNumber, email, name, surname, amount, status, pgOrderId, pgHppUrl, pgPassword (encrypted), qrCode (encrypted), scannedAt, scannedBy | Read-only in admin |
| Product | `products` | title (json), description (json), priceGel, category, isVip, status | Translatable |
| ProductSize | `product_sizes` | product_id, size, quantity | Pivot for sizes |
| ProductOrder | `product_orders` | productId, productTitle, size, name, email, phone, amount, status, pgOrderId, pgPassword (encrypted) | Read-only in admin |
| JokerTicket | `joker_tickets` | tracking fields | Special category |
| MusicTrack | `music_tracks` | title (json), artist, audioFile (media_id), order, status | Sortable, translatable |
| Page | `pages` | title (json), navLabel (json), slug, routePath, showInNav, navOrder, featuredOnHome, layout, contentBlocks (json), is_published | Versioning via spatie/laravel-activitylog |
| Post | `posts` | title (json), body (json), slug, status | Translatable |
| Partner | `partners` | name, logo (media_id), url, order | Sortable |
| Media | `media` | filename, path, mime_type, size, alt | Local disk, Nginx serves `/storage/` |
| SiteSetting | `site_settings` | key, value (json) | Single config table |

### Multilingual Strategy

**JSON columns** with `spatie/laravel-translatable`:

```php
// Model
class Ticket extends Model
{
    use HasTranslations;
    public array $translatable = ['title', 'description'];
}

// Usage
$ticket->setLocale('ka')->title; // Georgian
$ticket->setLocale('en')->title; // English
```

Languages: `ka` (default), `en`, `ru`, `ua`

### Data Migration

One-time Artisan command: reads existing Payload PostgreSQL tables, transforms and seeds into new Laravel schema. Not a continuous sync.

---

## 4. Security

### Middleware Stack (order matters)

```
1. ForceHttps              — redirect HTTP → HTTPS (production)
2. TrustProxies            — Nginx reverse proxy headers
3. CORS                    — strict origin whitelist (tbilisistyle.ge only)
4. ThrottleRequests        — per-route rate limits
5. Sanctum::statefulApi    — cookie auth for SPA
6. VerifyCsrfToken         — Filament admin sessions
7. EncryptCookies          — all cookies encrypted
8. Controller              — business logic
```

### Rate Limits

| Route Group | Limit | Key |
|---|---|---|
| `api/*` (public) | 60/min | IP |
| `api/admin/login` | 5/min | IP |
| `api/orders/*` | 10/min | IP |
| `api/payments/callback` | 30/min | IP |
| `api/check-personal-number` | 10/min | IP |

### Authentication & Authorization

- **Sanctum SPA auth**: Cookie-based for Next.js frontend → Laravel API (same domain)
- **Sanctum tokens**: For any future mobile/external API access
- **Laravel Policies**: Model-level authorization — admin can CRUD all, editor restricted
- **Filament Shield** (optional): Role-based admin panel permissions

### Encryption at Rest

```php
// Encrypted fields (Laravel Crypt facade)
SoldTicket::pgPassword    → encrypted
SoldTicket::qrCode        → encrypted
ProductOrder::pgPassword  → encrypted
// Quipu mTLS certs stored in /etc/ssl/private/ on VPS, not in DB
```

### Payment Security (Quipu)

- mTLS: Laravel HTTP client with cert/key options
- HMAC callback verification: dedicated `VerifyQuipuHmac` middleware
- Callback IP whitelist (configurable in .env)
- Payment fields never exposed in public API responses

### Input Validation

Every endpoint has a `FormRequest` class:

```php
class CreateTicketOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ticketId'       => ['required', 'uuid', 'exists:tickets,id'],
            'name'           => ['required', 'string', 'max:100'],
            'surname'        => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email:rfc,dns'],
            'personalNumber' => ['required', 'string', 'digits:11'],
            'quantity'       => ['required', 'integer', 'min:1', 'max:3'],
        ];
    }
}
```

### PgAdmin Security

| Environment | Protection |
|---|---|
| Local | `localhost:5050` only, Docker network, `.env` credentials |
| VPS | Nginx reverse proxy + SSL + HTTP Basic Auth, UFW firewall — only admin IP or VPN |

### Server Hardening (VPS)

- UFW: only ports 80, 443, 22
- SSH: key-only auth, root login disabled, Fail2ban
- Docker: non-root containers
- PostgreSQL: listens only on Docker internal network (not 0.0.0.0)
- `.env` files: `chmod 600`, not in git
- `APP_DEBUG=false` in production
- Fail2ban: SSH + Nginx brute force protection
- Security headers via Nginx: HSTS, X-Frame-Options, X-Content-Type-Options, CSP

---

## 5. API Endpoints

### Public (no auth)

```
GET    /api/tickets              → TicketController@index (cached)
GET    /api/tickets/{id}         → TicketController@show
GET    /api/products             → ProductController@index (cached)
GET    /api/products/{id}        → ProductController@show
GET    /api/music-tracks         → MusicTrackController@index (cached)
GET    /api/pages/{slug}         → PageController@show
GET    /api/posts                → PostController@index
GET    /api/partners             → PartnerController@index
GET    /api/site-settings        → SiteSettingController@index (cached)
GET    /api/locale               → LocaleController@show
```

### Payment (rate limited, no auth)

```
POST   /api/orders/tickets       → TicketOrderController@store
POST   /api/orders/products      → ProductOrderController@store
GET    /api/payments/callback     → PaymentCallbackController@handle (Quipu HMAC)
GET    /api/payments/redirect     → PaymentCallbackController@redirect
POST   /api/check-personal-number → PersonalNumberController@check
```

### Admin (Sanctum auth required)

```
POST   /api/admin/login          → AuthController@login
POST   /api/admin/logout         → AuthController@logout
GET    /api/admin/user           → AuthController@user
POST   /api/admin/validate-ticket → TicketScannerController@validate
```

### Caching Strategy

```php
// Laravel Cache (Redis or file driver)
Cache::remember('tickets:all', 3600, fn () => Ticket::active()->get());
Cache::remember('site-settings', 3600, fn () => SiteSetting::all());
Cache::remember('music-tracks', 3600, fn () => MusicTrack::ordered()->get());

// Cache invalidation: Filament afterSave/afterDelete hooks
```

---

## 6. Application Layer Structure

### Principle

- **Controller** — thin, request/response only, delegates to Action or Service
- **Service** — reusable business logic (queries, caching, external APIs)
- **Action** — single-responsibility orchestration (combines multiple services)
- **Model** — data, relationships, scopes, translatable traits
- **Policy** — authorization rules
- **Job** — async background tasks (replaces Payload MessageJobs)
- **FormRequest** — input validation

### Directory Structure

```
app/
├── Models/
│   ├── User.php
│   ├── Ticket.php
│   ├── SoldTicket.php
│   ├── Product.php
│   ├── ProductSize.php
│   ├── ProductOrder.php
│   ├── JokerTicket.php
│   ├── MusicTrack.php
│   ├── Page.php
│   ├── Post.php
│   ├── Partner.php
│   ├── Media.php
│   └── SiteSetting.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── TicketController.php
│   │   │   ├── ProductController.php
│   │   │   ├── MusicTrackController.php
│   │   │   ├── PageController.php
│   │   │   ├── PostController.php
│   │   │   ├── PartnerController.php
│   │   │   ├── SiteSettingController.php
│   │   │   ├── LocaleController.php
│   │   │   └── PersonalNumberController.php
│   │   ├── Payment/
│   │   │   ├── TicketOrderController.php
│   │   │   ├── ProductOrderController.php
│   │   │   └── PaymentCallbackController.php
│   │   └── Admin/
│   │       ├── AuthController.php
│   │       └── TicketScannerController.php
│   ├── Requests/
│   │   ├── CreateTicketOrderRequest.php
│   │   ├── CreateProductOrderRequest.php
│   │   └── CheckPersonalNumberRequest.php
│   └── Middleware/
│       ├── VerifyQuipuHmac.php
│       └── LocaleFromHeader.php
│
├── Services/
│   ├── TicketService.php
│   ├── ProductService.php
│   ├── PaymentService.php
│   ├── QrCodeService.php
│   ├── PdfService.php
│   ├── EmailService.php
│   └── SiteSettingService.php
│
├── Actions/
│   ├── CreateTicketOrderAction.php
│   ├── CreateProductOrderAction.php
│   ├── ProcessPaymentCallbackAction.php
│   ├── ValidateTicketAction.php
│   ├── SendTicketEmailAction.php
│   └── MigratePayloadDataAction.php
│
├── Policies/
│   ├── TicketPolicy.php
│   ├── SoldTicketPolicy.php
│   ├── ProductPolicy.php
│   └── ...
│
├── Jobs/
│   ├── SendTicketEmailJob.php
│   └── SendProductOrderEmailJob.php
│
└── Filament/
    ├── Resources/
    │   ├── TicketResource.php
    │   ├── SoldTicketResource.php
    │   ├── ProductResource.php
    │   ├── ProductOrderResource.php
    │   ├── MusicTrackResource.php
    │   ├── PageResource.php
    │   ├── PostResource.php
    │   ├── PartnerResource.php
    │   └── MediaResource.php
    ├── Pages/
    │   ├── Dashboard.php
    │   ├── TicketScanner.php
    │   ├── ActivityLog.php
    │   └── NavigationMenu.php
    └── Widgets/
        ├── RevenueChart.php
        ├── SalesBreakdown.php
        └── InventoryAlerts.php
```

---

## 7. Filament Admin Panel

Replaces Payload CMS admin entirely. Maps all existing admin features:

| Payload Feature | Filament Equivalent |
|---|---|
| Collection CRUD | Filament Resources (auto-generated forms, tables, filters) |
| Dashboard with KPIs | Custom Dashboard Page + Widgets (ApexCharts) |
| QR Ticket Scanner | Custom Filament Page with camera JS (html5-qrcode) |
| Activity/Transaction Log | Custom Page with Filament Tables |
| Navigation Menu Editor | Custom Page with drag-sort (SortableTrait) |
| Draft/Publish Pages | Filament Resource with status toggle + preview |
| Media Library | Filament Media Resource with upload + Nginx serving |
| Multilingual Tabs | `spatie/laravel-translatable` + Filament Translatable plugin |
| Email Queue Monitor | MessageJob Resource (read-only table, status filters) |

### Key Filament Packages

```
filament/filament                 — core admin panel
filament/spatie-laravel-translatable-plugin — multilingual forms
filament/spatie-laravel-media-library-plugin — media management (optional)
```

---

## 8. Next.js Frontend Migration

### What Changes

| Layer | Before (Payload) | After (Laravel API) |
|---|---|---|
| Data fetching | `payload.find()` / Payload REST | `fetch('/api/tickets')` via Laravel API |
| Auth (admin) | Payload auth + custom session | Sanctum cookie auth |
| Image URLs | Vercel Blob URLs | `/storage/media/{filename}` (Nginx) |
| Cache | `unstable_cache()` | Same, but data source changes to Laravel API |
| i18n | next-intl (unchanged) | next-intl (unchanged) |
| Components | React components (unchanged) | React components (unchanged) |
| Styles | Tailwind CSS (unchanged) | Tailwind CSS (unchanged) |

### What Stays The Same

- All React components in `app/(frontend)/`
- `next-intl` configuration and message files
- Tailwind CSS styles
- Page routing structure (`/dashboard/*`, `/[slug]`)
- Client-side interactivity (forms, modals, music player)

### Data Fetching Wrapper

```typescript
// lib/api.ts — replaces lib/payload.ts
const API_URL = process.env.NEXT_PUBLIC_API_URL; // http://localhost:8000 or https://api.tbilisistyle.ge

export async function api<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      'Accept': 'application/json',
      'Accept-Language': locale,
      ...options?.headers,
    },
    credentials: 'include', // Sanctum cookies
  });
  if (!res.ok) throw new ApiError(res.status, await res.json());
  return res.json();
}
```

---

## 9. Docker Compose

### Production (VPS)

```yaml
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d
      - certbot-etc:/etc/letsencrypt
      - laravel-storage:/var/www/html/storage/app/public
    depends_on: [laravel, nextjs]

  laravel:
    build: ./backend
    expose: ["8000"]
    env_file: ./backend/.env
    volumes:
      - laravel-storage:/var/www/html/storage/app/public
    depends_on: [postgres]

  nextjs:
    build: ./frontend
    expose: ["3000"]
    env_file: ./frontend/.env
    depends_on: [laravel]

  postgres:
    image: postgres:16-alpine
    volumes:
      - pgdata:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: tbilisistyle
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    # NOT exposed to host — only Docker internal network

  pgadmin:
    image: dpage/pgadmin4
    environment:
      PGADMIN_DEFAULT_EMAIL: ${PGADMIN_EMAIL}
      PGADMIN_DEFAULT_PASSWORD: ${PGADMIN_PASSWORD}
    expose: ["5050"]
    depends_on: [postgres]
    # Accessed via Nginx reverse proxy (IP-restricted)

  certbot:
    image: certbot/certbot
    volumes:
      - certbot-etc:/etc/letsencrypt

volumes:
  pgdata:
  laravel-storage:
  certbot-etc:
```

### Development (Local)

```yaml
# docker-compose.dev.yml
services:
  postgres:
    image: postgres:16-alpine
    ports: ["5432:5432"]
    volumes:
      - pgdata-dev:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: tbilisistyle
      POSTGRES_USER: tbilisistyle
      POSTGRES_PASSWORD: secret

  pgadmin:
    image: dpage/pgadmin4
    ports: ["5050:80"]
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@tbilisistyle.ge
      PGADMIN_DEFAULT_PASSWORD: admin
    depends_on: [postgres]

  mailpit:
    image: axllent/mailpit
    ports: ["8025:8025", "1025:1025"]

volumes:
  pgdata-dev:
```

Local development: `docker compose -f docker-compose.dev.yml up -d` starts PostgreSQL + PgAdmin + Mailpit. Then `php artisan serve` and `npm run dev` separately.

PgAdmin locally at `http://localhost:5050` — full database visibility.

---

## 10. Project Directory Structure

```
TbilisiStyle21/
├── backend/                        # Laravel application
│   ├── app/
│   │   ├── Models/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Requests/
│   │   │   └── Middleware/
│   │   ├── Services/
│   │   ├── Actions/
│   │   ├── Policies/
│   │   ├── Jobs/
│   │   └── Filament/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php (Filament)
│   ├── config/
│   ├── storage/
│   ├── Dockerfile
│   ├── composer.json
│   └── .env
│
├── frontend/                       # Next.js application
│   ├── app/                        # migrated from current app/(frontend)/
│   ├── components/
│   ├── lib/
│   │   ├── api.ts                  # Laravel API client (replaces payload.ts)
│   │   └── ...
│   ├── i18n/
│   ├── public/
│   ├── Dockerfile
│   ├── package.json
│   └── .env
│
├── nginx/
│   └── conf.d/
│       ├── default.conf            # production routing
│       └── ssl.conf
│
├── docker-compose.yml              # production
├── docker-compose.dev.yml          # local development
├── docs/
│   └── superpowers/specs/
└── CLAUDE.md
```

---

## 11. Key Laravel Packages

```
laravel/sanctum                    — SPA + API authentication
laravel/framework                  — core
filament/filament                  — admin panel
filament/spatie-laravel-translatable-plugin — multilingual admin forms
spatie/laravel-translatable        — model translations (JSON columns)
spatie/laravel-activitylog         — page versioning / audit trail
barryvdh/laravel-dompdf            — PDF generation (ticket PDFs)
simplesoftwareio/simple-qrcode     — QR code generation
resend/resend-laravel              — email via Resend API
```

---

## 12. Migration Checklist (High-Level)

1. Scaffold Laravel project in `backend/`
2. Set up Docker Compose (dev) — PostgreSQL + PgAdmin + Mailpit
3. Create all migrations and models
4. Implement Services and Actions (business logic)
5. Implement API controllers + routes + FormRequests
6. Set up Sanctum authentication
7. Build Filament admin panel (Resources, Pages, Widgets)
8. Set up security middleware stack
9. Write data migration command (Payload → Laravel)
10. Create Next.js frontend in `frontend/` — copy components, replace data layer
11. Set up Docker Compose (production) — Nginx + SSL + all services
12. Test end-to-end: payment flow, admin panel, QR scanner, email delivery
13. Deploy to VPS
