# Tbilisi Style 21 — Redesign Preview (client review)

Static, client-facing preview of the festival site redesign. **Not production
code** — it exists to get the client's (დამკვეთი) sign-off on the visual design
before we port it into the real Next.js frontend + Filament backend.

- **Live preview:** `https://tatoGi.github.io/Tbilisistyl21-v2/` (after Pages is
  enabled — see below)
- **Start here:** `index.html` — a gallery linking every page.
- **Content is placeholder** — Georgian copy and images are representative, not final.

## Pages
`home` · `festival` · `show` (universal CMS template, ~12 pages) · `partners` ·
`tickets` · `shop` · `news` + `news-article` · `success` / `fail`.

Three of these (`show`, `partners`, `tickets`) come from the design handoff; the
rest were built in the same visual language to cover the whole site.

## How it's built
`build.py` assembles every page from shared partials (nav, footer bar) and the
design tokens in `assets/style.css`. After editing, regenerate:

```bash
python build.py
```

The committed `.html` files at the repo root are what GitHub Pages serves — no
build step runs on the server.

## Enabling GitHub Pages (one-time, manual)
GitHub → repo **Settings → Pages** → Source: **Deploy from a branch** → Branch:
**`gh-pages`** / **`/ (root)`** → Save. The URL appears after ~1 minute.

## Migration plan
The full design + data-safety strategy (how the new design maps onto the existing
database with nothing lost and everything staying dynamic) is in
`docs/superpowers/specs/2026-07-11-festival-redesign-preview-design.md`.
