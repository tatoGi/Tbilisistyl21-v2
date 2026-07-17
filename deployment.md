# Deployment — უსაფრთხო განახლება სერვერზე

ეს დოკუმენტი აღწერს **არსებული** production სერვერის (`Hetzner, /opt/tbilisistyle`)
განახლებას. პირველი ინსტალაციისთვის იხ. `README.md` → „ვარიანტი B — Production".

## რა არის დაცული (deploy-ს არ ეშინია)

| რა | სად ინახება | რატომ არის უსაფრთხო |
|----|-------------|---------------------|
| ბაზა (შეკვეთები, ბილეთები, კონტენტი) | `pgdata` named volume | `git pull`/`up --build` volume-ს არ ეხება |
| ატვირთული მედია (სურათები, ლოგოები) | `laravel-storage` named volume | იგივე |
| კონფიგი/საიდუმლოებები | `backend/.env`, root `.env` | ორივე `.gitignore`-შია — pull ვერ გადააწერს |

`config:cache`/`route:cache` კონტეინერის **სტარტზე** კეთდება (`backend/docker/entrypoint.sh`),
ამიტომ კონტეინერის ხელახლა შექმნა ყოველთვის ახალ `.env`-ს კითხულობს.

## სტანდარტული განახლება (ნაბიჯ-ნაბიჯ)

```bash
cd /opt/tbilisistyle

# 0. ბაზის სწრაფი backup (30 წამი — ყოველთვის გააკეთე)
docker compose exec postgres pg_dump -U tbilisistyle tbilisistyle \
  | gzip > ~/backups/db-$(date +%F-%H%M).sql.gz

# 1. კოდის განახლება (--ff-only: თუ სერვერზე ლოკალური ცვლილებაა, გაჩერდება და არ გადააწერს)
git pull --ff-only

# 2. იმიჯების გადაბილდვა და კონტეინერების განახლება
#    (მხოლოდ შეცვლილი სერვისები იქმნება ხელახლა; volume-ები უცვლელი რჩება)
docker compose up -d --build

# 3. მიგრაციები (მხოლოდ migrate — არასდროს fresh/refresh/rollback)
docker compose exec laravel php artisan migrate --force

# 4. შემოწმება
docker compose ps                          # ყველა სერვისი "Up" უნდა იყოს
docker compose logs --tail=50 laravel      # შეცდომები ხომ არ არის
docker compose logs --tail=50 queue        # queue worker მუშაობს
curl -s https://<დომენი>/api/site-settings | head -c 200   # API პასუხობს
```

ეს არის სრული პროცედურა. `db:seed` განახლებისას **არ გაუშვა** — ის მხოლოდ
პირველი ინსტალაციისთვისაა (ხელახლა გაშვება კონტენტს/ადმინს გადააწერს ან
დააჭედებს).

## თუ მხოლოდ `.env` შეიცვალა (კოდი არა)

```bash
docker compose up -d --force-recreate laravel queue
```

`restart`-ის ნაცვლად `--force-recreate` გამოიყენე — ორივე შემთხვევაში entrypoint
თავიდან დააქეშავს კონფიგს, მაგრამ recreate გარანტიით იღებს `env_file`-ის ახალ
მნიშვნელობებს.

## ⛔ აკრძალული ბრძანებები production-ზე

| ბრძანება | რას დააზიანებს |
|----------|----------------|
| `docker compose down -v` | **წაშლის ბაზას და ატვირთულ მედიას** (`-v` volume-ებს შლის) |
| `php artisan migrate:fresh` / `migrate:refresh` | ბაზას აცარიელებს და თავიდან აშენებს |
| `php artisan db:seed` (განახლებისას) | კონტენტს/ადმინის პაროლს გადააწერს |
| `php artisan migrate:rollback` | ბოლო მიგრაციის მონაცემებს შლის — მხოლოდ backup-ის ქონისას, გააზრებულად |
| `git reset --hard` / `git checkout -- .` სერვერზე | სერვერზე ხელით შესწორებულ ფაილებს შლის — ჯერ `git status`-ით ნახე რა იცვლება |

უბრალო `docker compose down` (`-v`-ს გარეშე) უსაფრთხოა, მაგრამ საიტს აჩერებს —
განახლებას `up -d --build` საკმარისია, `down` არ სჭირდება.

## Rollback (თუ deploy-მ რამე გატეხა)

```bash
cd /opt/tbilisistyle
git log --oneline -5                 # იპოვე წინა მუშა commit
git checkout <წინა-commit-hash>
docker compose up -d --build

# ბაზის აღდგენა მხოლოდ მაშინ, თუ ცუდმა მიგრაციამ დააზიანა მონაცემები:
gunzip -c ~/backups/db-<თარიღი>.sql.gz \
  | docker compose exec -T postgres psql -U tbilisistyle tbilisistyle
```

დაბრუნება უახლეს კოდზე: `git checkout main && git pull --ff-only`.

## Deploy-ის შემდგომი ვერიფიკაცია (გადახდები)

```bash
# გადახდის ლოგი ცოცხლად:
docker compose exec laravel tail -f storage/logs/payment-$(date +%F).log
```

სატესტო ყიდვისას ლოგში უნდა ჩანდეს: `quipu.createOrder request` → `response` →
`redirect: forwarding buyer to HPP` → `callback: received` → `callback: result {"status":200}`.

## ცნობილი ნიუანსები

- **`.env`-ში დუბლირებული ცვლადები**: Laravel პირველ შემხვედრ მნიშვნელობას იღებს.
  სერვერზე ერთხელ უკვე გვქონდა ეს პრობლემა (`PG_*` ორჯერ ეწერა — ცარიელი ჯერ).
  შემოწმება: `grep -c "^PG_MERCHANT" backend/.env` — შედეგი 1 უნდა იყოს.
- **queue worker**: `up -d --build` ავტომატურად ანახლებს queue კონტეინერსაც.
  თუ იმიჯი არ შეცვლილა და მაინც გინდა worker-ის გადატვირთვა:
  `docker compose restart queue`.
- **მიგრაციები additive უნდა იყოს** (ახალი ცხრილი/სვეტი, nullable default-ით) —
  სვეტის წაშლა/გადარქმევა deploy-ისას downtime-სა და მონაცემის კარგვას იწვევს.
