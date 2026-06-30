# TbilisiStyle21

ბილეთების გაყიდვისა და ღონისძიების მართვის პლატფორმა — **Laravel API + Filament ადმინ პანელი** backend-ზე და **Next.js** frontend-ზე. სისტემა ამუშავებს QR ბილეთებს, პროდუქტების შეკვეთებს, ონლაინ გადახდას (Quipu payment gateway), მრავალენოვან კონტენტს და ბილეთების სკანირებას შესასვლელთან.

---

## 📋 პროექტის აღწერა

TbilisiStyle21 არის სრული full-stack გადაწყვეტა ღონისძიებისთვის, რომელიც შედგება ორი დამოუკიდებელი აპლიკაციისგან:

- **Backend** (`backend/`) — Laravel 13 REST API + Filament 3 ადმინ პანელი. ეს არის სისტემის გული: ბაზა, ბიზნეს-ლოგიკა, გადახდები, ბილეთების გენერაცია/ვალიდაცია.
- **Frontend** (`frontend/`) — Next.js 15 (App Router) + React 19 + Tailwind CSS 4. საჯარო საიტი, რომელიც მოიხმარს Laravel API-ს და ემსახურება მომხმარებლებს.

### ძირითადი ფუნქციონალი

| ფუნქცია | აღწერა |
|---------|--------|
| 🎟️ ბილეთები | ბილეთების გაყიდვა, QR კოდის გენერაცია, შესასვლელთან სკანირება/ვალიდაცია |
| 🛍️ პროდუქტები | მაღაზია ზომებით (sizes), შეკვეთები, მარაგის (inventory) კონტროლი |
| 💳 გადახდა | ონლაინ გადახდა **Quipu payment gateway**-ით (HMAC callback-ით დაცული) |
| 🌐 მრავალენოვნება | კონტენტი 4 ენაზე — ქართული (ka), ინგლისური (en), რუსული (ru), უკრაინული (ua) |
| 📄 კონტენტი | გვერდები, პოსტები, პარტნიორები, მუსიკალური ტრეკები, საიტის პარამეტრები |
| 🔐 ადმინ პანელი | Filament-ზე აგებული მართვის პანელი role-based წვდომით |
| 🆔 პერსონალური ნომერი | ბილეთის ლიმიტი თითო პირადი ნომერზე (`MAX_TICKETS_PER_PERSON`) |

### ტექნოლოგიური სტეკი

**Backend**
- PHP 8.3, Laravel 13
- Filament 3 (ადმინ პანელი)
- Laravel Sanctum (API ავთენტიფიკაცია)
- PostgreSQL 16
- spatie/laravel-translatable (თარგმანები), simple-qrcode (QR), barryvdh/laravel-dompdf (PDF), spatie/laravel-activitylog (აუდიტი)

**Frontend**
- Next.js 15 (App Router, standalone build)
- React 19, TypeScript
- Tailwind CSS 4
- next-intl (i18n)

**Infrastructure**
- Docker / Docker Compose
- Nginx (reverse proxy + TLS)
- Certbot (Let's Encrypt SSL)
- PgAdmin, Mailpit (dev)

---

## 📁 პროექტის სტრუქტურა

```
TbilisiStyle21-v2/
├── backend/                 # Laravel API + Filament ადმინ პანელი
│   ├── app/                 # მოდელები, კონტროლერები, სერვისები, Filament Resources
│   ├── routes/api.php       # API endpoint-ები
│   ├── database/            # მიგრაციები და seeder-ები
│   ├── docker/              # PHP dev კონფიგი (php-dev.ini)
│   ├── Dockerfile           # production image (php-fpm-alpine)
│   └── Dockerfile.dev       # development image (php-cli + artisan serve)
├── frontend/                # Next.js აპლიკაცია
│   ├── app/                 # App Router გვერდები და კომპონენტები
│   ├── messages/            # თარგმანები (en/ka/ru/ua.json)
│   └── Dockerfile           # production image (multi-stage)
├── nginx/conf.d/            # Nginx reverse proxy კონფიგი
├── docker-compose.yml       # PRODUCTION სტეკი
├── docker-compose.dev.yml   # DEVELOPMENT-ის სერვისები (DB, mail, ა.შ.)
└── .env.example             # root-ის გარემოს ცვლადები (prod)
```

---

## 🚀 პროექტის გაშვება

### წინაპირობები
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Compose v2-ით)
- Git

### ვარიანტი A — Development (Docker)

ეს არის რეკომენდებული გზა ლოკალური განვითარებისთვის. `docker-compose.dev.yml` უშვებს PostgreSQL-ს, PgAdmin-ს, Mailpit-ს და Laravel backend-ს hot-reload-ით.

```bash
# 1. გაუშვი dev სტეკი (DB + Mailpit + Laravel backend)
docker compose -f docker-compose.dev.yml up -d --build

# 2. backend-ის პირველადი მომზადება (იხ. ქვემოთ "Backend Docker-ში")

# 3. frontend-ი ცალკე გაუშვი (frontend არ შედის dev compose-ში)
cd frontend
npm install
npm run dev
```

**Dev სერვისების მისამართები:**

| სერვისი | URL |
|---------|-----|
| Backend API / ადმინ პანელი | http://localhost:8000 |
| Filament ადმინი | http://localhost:8000/admin |
| Frontend (Next.js) | http://localhost:3000 |
| PgAdmin | http://localhost:5050 |
| Mailpit (email viewer) | http://localhost:8026 |
| PostgreSQL | localhost:5432 |

### ვარიანტი B — Production (Docker)

`docker-compose.yml` ამუშავებს სრულ production სტეკს Nginx reverse proxy-ით, queue worker-ით, scheduler-ით და SSL-ით.

```bash
# 1. შექმენი root .env root-ის .env.example-დან
cp .env.example .env

# 2. შექმენი backend/.env backend/.env.example-დან და შეავსე
cp backend/.env.example backend/.env

# 3. გაუშვი მთლიანი სტეკი
docker compose up -d --build

# 4. გაუშვი მიგრაციები და seeder-ები
docker compose exec laravel php artisan migrate --force
docker compose exec laravel php artisan db:seed --force
```

Production სტეკის სერვისები: `nginx`, `laravel` (php-fpm), `queue`, `scheduler`, `nextjs`, `postgres`, `pgadmin`, `certbot`.

---

## 🐳 Backend-ის გაშვება Docker-ში (დეტალურად)

Backend-ს ორი Dockerfile აქვს:

- **`backend/Dockerfile.dev`** — development-ისთვის. იყენებს `php:8.3-cli`-ს, რომელიც `artisan serve`-ით უშვებს სერვერს `0.0.0.0:8000`-ზე. `vendor/` ცალკე named volume-ში ცხოვრობს (Windows bind mount-ის სიჩქარის პრობლემის გადასაჭრელად).
- **`backend/Dockerfile`** — production-ისთვის. იყენებს `php:8.3-fpm-alpine`-ს, აქესავებს კონფიგს/route-ებს/view-ებს და OPcache-ს production რეჟიმში.

### Development backend ნაბიჯ-ნაბიჯ

```bash
# 1. გაუშვი backend + DB კონტეინერები
docker compose -f docker-compose.dev.yml up -d --build

# 2. (პირველი გაშვება) დააკოპირე .env
docker compose -f docker-compose.dev.yml exec laravel cp .env.example .env

# 3. დააგენერირე აპლიკაციის გასაღები
docker compose -f docker-compose.dev.yml exec laravel php artisan key:generate

# 4. გაუშვი მიგრაციები
docker compose -f docker-compose.dev.yml exec laravel php artisan migrate

# 5. seeder-ები (ქმნის ადმინ მომხმარებელს და საწყის კონტენტს)
docker compose -f docker-compose.dev.yml exec laravel php artisan db:seed

# 6. storage symlink (ფაილების/მედიის ჩვენებისთვის)
docker compose -f docker-compose.dev.yml exec laravel php artisan storage:link
```

backend ხელმისაწვდომი იქნება მისამართზე **http://localhost:8000**, ხოლო ადმინ პანელი — **http://localhost:8000/admin**.

### ადმინ პანელში შესვლა (default credentials)

`AdminSeeder`-ი ქმნის ტესტურ ადმინს:

```
Email:    admin@tbilisistyle.ge
Password: secret
```

> ⚠️ production-ში აუცილებლად შეცვალე ეს პაროლი.

### სასარგებლო Docker ბრძანებები

```bash
# ლოგების ნახვა
docker compose -f docker-compose.dev.yml logs -f laravel

# artisan ბრძანების გაშვება კონტეინერში
docker compose -f docker-compose.dev.yml exec laravel php artisan <command>

# tinker (ინტერაქტიული shell)
docker compose -f docker-compose.dev.yml exec laravel php artisan tinker

# ქეშის გასუფთავება
docker compose -f docker-compose.dev.yml exec laravel php artisan optimize:clear

# კონტეინერების გაჩერება
docker compose -f docker-compose.dev.yml down

# ბაზის წაშლა და სუფთა გაშვება (volume-ების ჩათვლით)
docker compose -f docker-compose.dev.yml down -v
```

### ტესტების გაშვება

```bash
docker compose -f docker-compose.dev.yml exec laravel php artisan test
```

---

## 🔧 გარემოს ცვლადები (Environment)

backend-ის კონფიგი — `backend/.env` (იხ. `backend/.env.example`). მნიშვნელოვანი ცვლადები:

| ცვლადი | აღწერა |
|--------|--------|
| `APP_URL` | backend-ის მისამართი |
| `FRONTEND_URL` | Next.js frontend-ის მისამართი (CORS/redirect) |
| `DB_*` | PostgreSQL კავშირის პარამეტრები |
| `MAX_TICKETS_PER_PERSON` | ბილეთების ლიმიტი თითო პირადი ნომერზე |
| `SANCTUM_STATEFUL_DOMAINS` | SPA ავთენტიფიკაციის დომენები |
| `CORS_ALLOWED_ORIGINS` | დაშვებული origin-ები |
| `PG_*` | Quipu payment gateway-ის credentials (merchant id, სერტიფიკატები base64-ში) |
| `MAIL_*` | ფოსტის კონფიგი (dev-ში Mailpit) |

> 💳 **Quipu gateway**: production-ში `PG_API_URL`, `PG_MERCHANT_ID`, `PG_TYPE_RID` და base64 სერტიფიკატები (`PG_CERT_BASE64`, `PG_KEY_BASE64`, `PG_CA_BASE64`) აუცილებლად შესავსებია.

---

## 🌐 API Endpoint-ები (მოკლე მიმოხილვა)

```
GET  /api/locale                  # მიმდინარე ენა
GET  /api/tickets                 # ბილეთების სია
GET  /api/products                # პროდუქტები
GET  /api/music-tracks            # მუსიკალური ტრეკები
GET  /api/pages /api/pages/{slug} # კონტენტ-გვერდები
GET  /api/posts                   # პოსტები
GET  /api/partners                # პარტნიორები
GET  /api/site-settings           # საიტის პარამეტრები
POST /api/orders/tickets          # ბილეთის შეკვეთა
POST /api/orders/products         # პროდუქტის შეკვეთა
POST /api/check-personal-number   # პირადი ნომრის შემოწმება
POST /api/admin/login             # ადმინის ავტორიზაცია
POST /api/admin/validate-ticket   # ბილეთის ვალიდაცია (სკანერი, auth)
GET  /api/payments/callback       # Quipu callback (HMAC დაცული)
```

---

## 📝 შენიშვნები

- `frontend/` **არ** შედის `docker-compose.dev.yml`-ში — development-ში frontend-ი ცალკე გაუშვი (`npm run dev`). production `docker-compose.yml`-ში `nextjs` სერვისად შედის.
- pgdata და vendor named volume-ებში ცხოვრობს Windows/WSL2-ზე სიჩქარისთვის.
- Mailpit-ის SMTP პორტია `1025`, ხოლო web UI — `8026` (dev compose-ში გადატანილია host-ის 8026-ზე).
