# Tbilisi Style 21 — Festival Redesign: Client Preview + Migration Strategy

**Date:** 2026-07-11
**Status:** Approved design, pre-implementation
**Author:** Claude Code session

## 1. Overview

Two-phase initiative:

1. **Phase 1 (this spec):** Build a static, client-facing HTML preview of the full
   redesigned festival site, hosted on the repo's `gh-pages` branch. Purpose: get
   the client's (დამკვეთი) sign-off on the visual redesign **before** touching any
   production code.
2. **Phase 2 (after client approval):** Port the approved design into the real
   Next.js frontend + Filament backend, keeping every page fully dynamic and
   losing **nothing** from the database. This spec defines the data-safety
   strategy for that port so Phase 1 decisions don't paint us into a corner.

Design source: `design_handoff_festival_redesign/` (README + `show-page.html`,
`partners-page.html`, `tickets-page.html`). Handoff covers 3 pages; the rest are
designed here in the same visual language.

## 2. Design Tokens (from handoff README — treat as final)

**Colors**
- Base bg: `#0b0906`; elevated cards: `rgba(255,255,255,0.02–0.06)` over
  `1px solid rgba(255,255,255,0.08)` borders
- Accent gold (primary): `#e8b84b` — eyebrows, underlines, primary buttons, stat
  numbers, active states
- Accent pink (secondary, sparing): `#ff3fa4`
- Text: heading `#f8f4ec`, body `#cdc2b2`, muted/label `#a89a86`
- Logo tile bg (for partner logos): `#f5f0e6`; button text on gold: `#1a1206`

**Typography**
- Body: `Noto Sans Georgian` (400–800) — required for Georgian glyphs
- Display/label: `Unbounded` (500–800) — wordmark, eyebrows, stat/price numbers.
  Latin-only; Georgian display falls back to Noto Sans Georgian 800.
- Scale: hero H1 ~68–76/800, H2 ~42/800, card title ~22/700, body ~17–19/400 lh
  1.7–1.75, label/eyebrow ~12–13/700 tracking 0.14–0.22em uppercase

**Spacing / shape**
- Page max-width 1440px, h-padding 56px; section rhythm 40–100px
- Radius: 20px large cards, 16px grid tiles, 14px small tiles, 999px pills
- Borders: 1px solid rgba(255,255,255,0.08–0.12)

## 3. Phase 1 — Preview Site

### Build approach
Small static generator (`build.py`) in the `gh-pages` worktree. Shared partials
(head, nav, footer bar) + design-token CSS defined **once**; each page assembled
into plain static HTML committed at repo root. GitHub Pages serves the committed
HTML — no build step or runtime needed on the client's side. Rationale: DRY,
consistent nav/footer across 10 pages, zero JS dependency for core layout.

Not chosen: (B) hand-written per-page HTML — nav/footer duplication drifts;
(C) keeping the claude design-sync runtime (`support.js` + `{{ accent }}` +
`<image-slot>`) — fragile and inconsistent with the 6 new pages.

### Content
Placeholder Georgian copy (as in handoff) + neutral/gradient image placeholders.
No real photography or live data in the preview.

### Page inventory (10 files at repo root)
1. `index.html` — gallery/landing for the client: links every page, a "PREVIEW —
   not final" banner, one-line note per page.
2. `home.html` — "enter the energy" splash (from `SiteSetting` landing in prod).
3. `festival.html` — festival hub (entry target of the home button).
4. `show.html` — universal CMS content template (covers ~12 pages: main-stage,
   qvevri-stage, techno-qvevri, four-stages, food-zone, lineup, mission,
   our-story, joker-ticket, rules-and-terms, ukrainian-day, contact-us, `[slug]`).
   ← handoff `show-page.html`.
5. `partners.html` — Title / Official / Media tiers. ← handoff.
6. `tickets.html` — pricing tiers, "popular" highlight, availability bar. ← handoff.
7. `shop.html` — product grid.
8. `news.html` — news/blog list.
9. `news-article.html` — single article.
10. `success.html` + `fail.html` — payment result pages.

Shared across all: sticky nav (language pill KA/EN/RU/UA + hamburger), sticky
footer bar (music-player pill + ticket-CTA pill).

### Deploy
Commit on `gh-pages` → push → GitHub → Settings → Pages → source `gh-pages` /
root → client URL `https://tatoGi.github.io/Tbilisistyl21-v2/`. `gh` CLI is not
installed, so enabling Pages is a one-time manual step in the GitHub UI.

## 4. Phase 2 — Migration Strategy (data-safety: nothing lost, stays dynamic)

**Guiding principle: the redesign is a presentation-layer change. Data contracts
stay intact; new capability is ADDITIVE only.** No column is dropped, renamed
destructively, or repurposed. No content-deleting data migration. Existing DB
rows keep rendering unchanged; the admin keeps full control of everything.

### 4.1 Every design section maps to an existing model

| Design section | Existing source (already in DB, admin-managed) | Gap → additive change |
| --- | --- | --- |
| Home splash | `SiteSetting` (landing title/subtitle/button/image) | none |
| Show/content pages | `Page.blocks` (hero, richText, image, gallery, cta, contact) | + optional block types (below) |
| Tickets | `Ticket` (title, description, image, price_gel, quantity, event_date, location, status, sale_url) + `SoldTicket` | + `category`, `features`, `is_featured` (all nullable) |
| Joker/VIP | `JokerTicket` | none (restyle only) |
| Partners | `Partner` (name, description, logo_id, url, order) | + `tier` enum (default `official`) |
| Shop | `Product` + `ProductSize` + `ProductOrder` | none (restyle only) |
| News list/article | `Post` (title, body, content_blocks, slug, status, featured) | none |
| Footer player | `MusicTrack` | none |
| Nav / contact | `SiteSetting`, `getSiteContact` | none |

### 4.2 New, additive Page block types (for the show template)
The new show design adds a **stat row** (date/location/duration) and **tag pills**
that the current 6 block types don't express. Add two OPTIONAL Filament blocks +
renderers in `RenderBlocks.tsx`:
- `eventInfo` — repeatable label/value rows (translatable), for the info card.
- `tags` — list of short translatable pills.

Existing pages have neither block and render exactly as before. Nothing forces a
re-author of current content.

### 4.3 New, additive model columns (nullable + defaults, translatable)
- `tickets`: `category` (json/translatable, nullable), `features` (json list,
  nullable), `is_featured` (boolean, default false).
- `partners`: `tier` (enum `title|official|media`, default `official`) so existing
  rows land in "Official" automatically — no manual backfill required.
- Availability % on ticket cards is **computed** from `quantity` − sold
  (`SoldTicket`); no stored field, no new data.

All migrations are `addColumn` with nullable/default — reversible, non-destructive.

### 4.4 Dynamic + i18n preservation
- All new fields follow the existing translatable JSON pattern and are read via the
  same `t(value, locale)` helper — 4 languages (KA/EN/RU/UA) preserved.
- Frontend keeps its current data-fetching/routing; only presentation
  (components/CSS/tokens) changes. Filament stays the single source of truth.
- Verification before/after the port: snapshot the API JSON for each page; after
  restyle the same JSON must still render (now themed). No API shape change.

### 4.5 Order of the Phase-2 port (later, its own plan)
1. Introduce design tokens (globals.css + Tailwind config) — no visual break.
2. Restyle the show template + `RenderBlocks` (existing blocks only).
3. Add `eventInfo` / `tags` blocks (additive) and their renderers.
4. Restyle tickets/partners/shop/news/home with additive columns behind them.
5. Per page: verify old DB content still renders, then wire the new fields.

## 5. Out of Scope
- No production code changes in Phase 1.
- No real photography / live data / real partner logos in the preview.
- No new payment or checkout behavior (restyle only).
- Phase-2 implementation plan is produced separately after client approval.
