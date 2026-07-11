# Festival Site Redesign — Implementation Plan (Phase 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the approved redesign into the real Next.js frontend + Filament backend, keeping every page fully dynamic and losing nothing from the database.

**Architecture:** Presentation-layer change. Existing data fetchers, routing, and CMS block model stay intact; the redesign restyles the components that render them. New dynamic capability is added **additively only** (nullable columns, optional Filament blocks) so pre-existing DB rows render unchanged. The approved static preview (`gh-pages` branch: `assets/style.css` + per-page `*.html`) is the **visual reference implementation** — frontend tasks translate that markup/CSS into the existing React components wired to live data.

**Tech Stack:** Next.js (App Router, RSC), Tailwind CSS v4 (`@theme` tokens), next-intl, next/font/google; Laravel + Filament v3, PHPUnit.

## Global Constraints

- **Additive-only DB:** no column dropped/renamed/repurposed; new columns nullable or with safe defaults; migrations reversible. Copied from spec §4.
- **Nothing lost / stays dynamic:** existing content renders unchanged after each task; Filament remains the single source of truth; API response shapes do not change.
- **i18n:** all new text fields are translatable JSON, read via `t(value, locale)` (`frontend/lib/utils.ts`); 4 languages KA/EN/RU/UA.
- **Out of scope — do NOT touch:** `frontend/app/page.tsx` (home "enter the energy") and `frontend/app/dashboard/festival/page.tsx` (festival) — keep as-is.
- **Design tokens (verbatim):** bg `#0b0906`; gold `#e8b84b`; pink `#ff3fa4`; text `#f8f4ec` / `#cdc2b2` / `#a89a86`; tile `#f5f0e6`; on-gold `#1a1206`; border `rgba(255,255,255,0.08)`. Display font `Unbounded`, body `Noto Sans Georgian`.
- **Reference:** static preview lives on branch `gh-pages` (worktree `../ts21-gh-pages`). Read `assets/style.css` and `<page>.html` there for exact markup/classes before restyling each page.
- **Verification note:** frontend visual work is verified by `npm run build` / `tsc` passing **and** driving the page in a browser (superpowers `run`/`verify` skill) to confirm old CMS/DB content still renders themed. Backend work is verified with PHPUnit.

---

## Task 1: Design foundation — tokens + Unbounded font

**Files:**
- Modify: `frontend/app/layout.tsx` (add `Unbounded` via `next/font/google`, expose `--font-unbounded`)
- Modify: `frontend/app/globals.css` (add redesign color tokens + `--font-display-un`, `.font-display` helper)

**Interfaces:**
- Produces: CSS custom properties `--ts-bg`, `--ts-gold`, `--ts-pink`, `--ts-head`, `--ts-body`, `--ts-muted`, `--ts-tile`, `--ts-on-gold`, `--ts-border`; utility class `.font-display` (Unbounded → Noto fallback). Consumed by all later frontend tasks.

- [ ] **Step 1: Add Unbounded font in layout**

In `frontend/app/layout.tsx`, add to the existing `next/font/google` import and instantiate:
```ts
import { Bebas_Neue, Noto_Sans_Georgian, Oswald, Unbounded } from "next/font/google";

const unbounded = Unbounded({
  subsets: ["latin"],
  weight: ["500", "700", "800"],
  variable: "--font-unbounded",
  display: "swap",
});
```
Append `unbounded.variable` to the `<html>`/`<body>` className list alongside the other font variables.

- [ ] **Step 2: Add tokens to globals.css**

Append to `frontend/app/globals.css`:
```css
:root {
  --ts-bg: #0b0906;
  --ts-gold: #e8b84b;
  --ts-pink: #ff3fa4;
  --ts-head: #f8f4ec;
  --ts-body: #cdc2b2;
  --ts-muted: #a89a86;
  --ts-tile: #f5f0e6;
  --ts-on-gold: #1a1206;
  --ts-border: rgba(255, 255, 255, 0.08);
}
.font-display { font-family: var(--font-unbounded), var(--font-noto-georgian, sans-serif); }
```
(Use the actual Noto variable name defined in layout.tsx.)

- [ ] **Step 3: Verify build + typecheck**

Run: `cd frontend && npm run build`
Expected: build succeeds, no TS errors; fonts resolve.

- [ ] **Step 4: Commit**

```bash
git add frontend/app/layout.tsx frontend/app/globals.css
git commit -m "feat(redesign): add Unbounded font + design tokens"
```

---

## Task 2: Restyle the show/content template (existing blocks)

**Files:**
- Modify: `frontend/app/components/CmsPageView.tsx` (hero/eyebrow/stats header per `show.html`)
- Modify: `frontend/app/components/RenderBlocks.tsx` (restyle `hero`, `richText`, `image`, `gallery`, `cta`, `contact` to new tokens)
- Reference: `../ts21-gh-pages/show.html`, `../ts21-gh-pages/assets/style.css`

**Interfaces:**
- Consumes: tokens/`.font-display` from Task 1. No prop/signature changes to `RenderBlocks` or `CmsPageView` — same `blocks`/`locale`/`contact` inputs.

- [ ] **Step 1: Snapshot current CMS output (baseline)**

Run the dev app (`run` skill), open `/dashboard/main-stage`, confirm current blocks render. Note the block set so Step 4 can confirm parity.

- [ ] **Step 2: Restyle CmsPageView header**

Replace the header/hero markup with the eyebrow + `hero__title` + optional stat row structure from `show.html` (classes ported into Tailwind utilities or a scoped stylesheet). Keep the existing `title`/`RenderBlocks` data flow.

- [ ] **Step 3: Restyle each existing block renderer**

In `RenderBlocks.tsx`, update the JSX/classes of `hero`, `richText`, `image`, `gallery`, `cta`, `contact` to the new palette/spacing (map from `assets/style.css`). Do not change which fields are read.

- [ ] **Step 4: Verify old content renders themed**

Run: `cd frontend && npm run build` then drive `/dashboard/main-stage`, `/dashboard/mission`, a `[slug]` page in the browser.
Expected: every previously-authored block still appears, now in the redesign theme. No missing content.

- [ ] **Step 5: Commit**

```bash
git add frontend/app/components/CmsPageView.tsx frontend/app/components/RenderBlocks.tsx
git commit -m "feat(redesign): restyle show/content template + existing blocks"
```

---

## Task 3: Additive Filament blocks — `eventInfo` + `tags`

**Files:**
- Modify: `backend/app/Filament/Concerns/HasContentBlocks.php` (register two new optional blocks)
- Modify: `frontend/app/components/RenderBlocks.tsx` (add renderers)
- Modify: `frontend/lib/pages.ts` / `frontend/lib/types.ts` if a block-type union is declared there

**Interfaces:**
- Produces: block types `eventInfo` (`{ label: translatable, rows: [{ k: translatable, v: translatable }], note: translatable }`) and `tags` (`{ items: translatable[] }`). Consumed by the show template render loop.

- [ ] **Step 1: Add blocks in Filament**

In `HasContentBlocks.php`, add two `Block::make('eventInfo')` and `Block::make('tags')` with translatable fields, mirroring the existing block definitions' translation pattern. Both entirely optional.

- [ ] **Step 2: Backend test — page saves/loads with new blocks and without**

Add a PHPUnit test (e.g. `backend/tests/Feature/ContentBlocksTest.php`):
```php
public function test_page_without_new_blocks_still_serializes(): void {
    $page = Page::factory()->create(['blocks' => [['type' => 'richText', 'data' => ['content' => ['ka' => 'x']]]]]);
    $this->assertSame('richText', $page->fresh()->blocks[0]['type']);
}
public function test_page_accepts_eventInfo_block(): void {
    $page = Page::factory()->create(['blocks' => [['type' => 'eventInfo', 'data' => ['label' => ['ka' => 'ღონისძიება'], 'rows' => [], 'note' => ['ka' => '']]]]]);
    $this->assertSame('eventInfo', $page->fresh()->blocks[0]['type']);
}
```

- [ ] **Step 3: Run backend tests**

Run: `cd backend && php artisan test --filter=ContentBlocksTest`
Expected: PASS. (Adjust factory usage to match the existing `Page` test setup.)

- [ ] **Step 4: Add frontend renderers**

In `RenderBlocks.tsx`, add `case "eventInfo"` (info-card markup from `show.html`) and `case "tags"` (tag pills). Existing pages lacking these blocks are unaffected (the `switch` default already returns `null`).

- [ ] **Step 5: Verify**

Run: `cd frontend && npm run build`; add an `eventInfo`/`tags` block to a test page in Filament and confirm it renders; confirm a page without them is unchanged.

- [ ] **Step 6: Commit**

```bash
git add backend/app/Filament/Concerns/HasContentBlocks.php backend/tests/Feature/ContentBlocksTest.php frontend/app/components/RenderBlocks.tsx frontend/lib/types.ts
git commit -m "feat(redesign): add optional eventInfo + tags CMS blocks"
```

---

## Task 4: Global chrome — nav restyle + sticky footer bar

**Files:**
- Modify: the shared nav/header component (locate via `grep -rl "lang" frontend/app/components`) — restyle language pill + hamburger per `assets/style.css`
- Create: `frontend/app/components/FooterBar.tsx` (music-player pill + ticket-CTA pill)
- Modify: `frontend/app/layout.tsx` or the dashboard layout to mount `FooterBar` on redesigned routes
- Reference: footer markup in `../ts21-gh-pages/show.html`; player data from `frontend/lib/music-tracks.ts`

**Interfaces:**
- Consumes: `getMusicTracks()` (existing) for the player title; `FooterBar` is a client component with a play/pause toggle.
- Produces: `<FooterBar track={...} />`.

- [ ] **Step 1: Restyle nav**

Update the nav component's classes to the redesign (sticky, blur, gold active language pill). Keep existing locale-switch links/handlers.

- [ ] **Step 2: Create FooterBar component**

Port the `.footerbar`/`.player`/`.cta-pill` markup into `FooterBar.tsx`; CTA links to the tickets route; player title from the first `MusicTrack`.

- [ ] **Step 3: Mount + verify**

Mount on redesigned pages; run `npm run build`; drive a page and confirm nav + footer bar render and language switch still works.

- [ ] **Step 4: Commit**

```bash
git add frontend/app/components frontend/app/layout.tsx
git commit -m "feat(redesign): restyle nav + add sticky footer bar"
```

---

## Task 5: Tickets — additive schema + redesigned tiers

**Files:**
- Create: `backend/database/migrations/2026_07_11_000000_add_redesign_fields_to_tickets_table.php`
- Modify: `backend/app/Models/Ticket.php` (fillable + casts)
- Modify: the Filament Ticket resource (add `category`, `features`, `is_featured` fields) — locate via `grep -rl "class TicketResource" backend`
- Modify: `frontend/lib/tickets.ts` (map new fields + computed `percentLeft`)
- Modify: `frontend/app/dashboard/tickets/page.tsx` (redesigned grid per `tickets.html`)
- Reference: `../ts21-gh-pages/tickets.html`

**Interfaces:**
- Produces: `Ticket` gains `category` (json/translatable, nullable), `features` (json array, nullable), `is_featured` (boolean default false). Frontend ticket type gains `category`, `features: string[]`, `isFeatured`, `percentLeft` (computed = round((quantity - sold) / quantity * 100)).

- [ ] **Step 1: Write migration**

```php
Schema::table('tickets', function (Blueprint $table) {
    $table->json('category')->nullable();
    $table->json('features')->nullable();
    $table->boolean('is_featured')->default(false);
});
```
`down()` drops the three columns.

- [ ] **Step 2: Run migration + test rollback safety**

Run: `cd backend && php artisan migrate` then `php artisan migrate:rollback --step=1` then `php artisan migrate`.
Expected: up/down both clean; existing ticket rows intact (nullable/default).

- [ ] **Step 3: Update model**

Add `category`, `features`, `is_featured` to `$fillable`; casts `category => 'array'`, `features => 'array'`, `is_featured => 'boolean'`.

- [ ] **Step 4: Backend test — existing tickets unaffected + availability computable**

```php
public function test_existing_ticket_defaults_are_safe(): void {
    $t = Ticket::factory()->create(['quantity' => 5000]);
    $this->assertFalse($t->is_featured);
    $this->assertNull($t->category);
}
```
Run: `php artisan test --filter=Ticket`
Expected: PASS.

- [ ] **Step 5: Filament fields (optional, non-required)**

Add the three fields to the Ticket resource form, all optional. Existing records edit without them set.

- [ ] **Step 6: Frontend mapping + page**

In `tickets.ts` map new fields and compute `percentLeft` from `quantity` and sold count (reuse existing sold source, e.g. `SoldTicket`/API). Rebuild `tickets/page.tsx` to the tier-card grid (`ticket.html` markup), featured card highlighted when `isFeatured`.

- [ ] **Step 7: Verify**

Run: `cd frontend && npm run build`; drive `/dashboard/tickets` — existing tickets render as cards (category/features empty gracefully), availability bar shows computed %.

- [ ] **Step 8: Commit**

```bash
git add backend/database/migrations backend/app/Models/Ticket.php backend/app/Filament frontend/lib/tickets.ts frontend/app/dashboard/tickets/page.tsx
git commit -m "feat(redesign): ticket tiers (additive category/features/is_featured)"
```

---

## Task 6: Partners — tier field + redesigned tiers page

**Files:**
- Create: `backend/database/migrations/2026_07_11_000100_add_tier_to_partners_table.php`
- Modify: `backend/app/Models/Partner.php` (fillable)
- Modify: Filament Partner resource (add `tier` select: title/official/media)
- Create/Modify: partners frontend page + data fetcher (locate current partners rendering; likely `PartnersStrip` component / a CMS route)
- Reference: `../ts21-gh-pages/partners.html`

**Interfaces:**
- Produces: `Partner.tier` enum string `title|official|media` default `official` (existing rows auto-land in "official" — no backfill).

- [ ] **Step 1: Write migration**

```php
Schema::table('partners', function (Blueprint $table) {
    $table->string('tier')->default('official');
});
```
`down()` drops `tier`.

- [ ] **Step 2: Migrate + verify existing rows default**

Run: `cd backend && php artisan migrate`; assert existing partners now have `tier = 'official'`.

- [ ] **Step 3: Model + Filament**

Add `tier` to `$fillable`; add a Filament select with the three options (default official).

- [ ] **Step 4: Backend test**

```php
public function test_existing_partner_defaults_to_official(): void {
    $p = Partner::factory()->create();
    $this->assertSame('official', $p->tier);
}
```
Run: `php artisan test --filter=Partner`  → PASS.

- [ ] **Step 5: Frontend partners page**

Group partners by `tier` (Title / Official / Media) with the light logo tiles from `partners.html` + the "become a partner" CTA banner.

- [ ] **Step 6: Verify + commit**

Run `npm run build`; drive the partners page (existing partners appear under Official). Then:
```bash
git add backend/database/migrations backend/app/Models/Partner.php backend/app/Filament frontend
git commit -m "feat(redesign): partner tiers (additive tier field)"
```

---

## Task 7: Shop — redesigned product grid (no schema change)

**Files:**
- Modify: `frontend/app/dashboard/shop/page.tsx`
- Reference: `../ts21-gh-pages/shop.html`; data via `frontend/lib/products.ts`

- [ ] **Step 1: Restyle product grid** using `product-card` markup, wired to existing `getProducts()` (title, price, image, sizes).
- [ ] **Step 2: Verify** `npm run build` + drive `/dashboard/shop` — existing products render as cards.
- [ ] **Step 3: Commit**
```bash
git add frontend/app/dashboard/shop/page.tsx
git commit -m "feat(redesign): restyle shop grid"
```

---

## Task 8: News — redesigned list + article (no schema change)

**Files:**
- Modify: `frontend/app/news/page.tsx`, `frontend/app/news/[slug]/page.tsx`
- Reference: `../ts21-gh-pages/news.html`, `news-article.html`; data via `frontend/lib/posts.ts`

- [ ] **Step 1: Restyle news list** (feature card uses `Post.featured`; grid cards from `news.html`).
- [ ] **Step 2: Restyle article** page (`prose` + `content_blocks` via existing renderer).
- [ ] **Step 3: Verify** `npm run build` + drive `/news` and an article — existing posts render.
- [ ] **Step 4: Commit**
```bash
git add frontend/app/news
git commit -m "feat(redesign): restyle news list + article"
```

---

## Task 9: Payment result pages — success / fail

**Files:**
- Modify: `frontend/app/dashboard/success/page.tsx`, `frontend/app/dashboard/fail/page.tsx`
- Reference: `../ts21-gh-pages/success.html`, `fail.html`

- [ ] **Step 1: Restyle both** with the `result` markup (keep existing order-lookup / query-param logic untouched).
- [ ] **Step 2: Verify** `npm run build` + drive both routes.
- [ ] **Step 3: Commit**
```bash
git add frontend/app/dashboard/success/page.tsx frontend/app/dashboard/fail/page.tsx
git commit -m "feat(redesign): restyle payment result pages"
```

---

## Task 10: Final verification + PR

- [ ] **Step 1: Full build + tests**

Run: `cd frontend && npm run build` (PASS) and `cd backend && php artisan test` (PASS).

- [ ] **Step 2: Regression sweep** — drive every redesigned route in the browser; confirm all pre-existing DB content renders (no blank sections). Confirm `home` and `festival` are untouched.

- [ ] **Step 3: Open PR** from `redesign/festival` → `main` summarizing the additive migrations and the page-by-page restyle.

## Notes on decomposition
Tasks 5–9 are independent and may be executed/reviewed in any order after Tasks 1–4 (foundation, template, chrome). If a subsystem grows large during execution, split its task per the writing-plans right-sizing guidance.
