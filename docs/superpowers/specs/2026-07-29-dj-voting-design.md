# DJ Voting — Design

**Date:** 2026-07-29
**Status:** Approved for planning

## Goal

Let the admin manage a roster of DJs and open time-boxed voting rounds. Visitors
to the festival page (`/dashboard/festival`) vote for the DJ they want to play.
The admin sees per-round vote statistics.

## Decisions

These were settled during brainstorming and drive everything below.

| Question | Decision |
|---|---|
| Who may vote | Anonymous — no login, no email, no ticket required |
| Duplicate protection | First-party httpOnly cookie token + hashed IP for audit |
| Ballot shape | One vote, one DJ, changeable while the round is open |
| Results visibility | Hidden until the visitor votes, then shown |
| Round control | Admin sets a start time and a duration; closing is automatic |

**Purpose of the vote is engagement and reach, not a binding decision.** The
anonymous model is therefore the right trade: it maximises participation and
accepts that a determined person can vote twice from a second browser. `ip_hash`
exists so the admin can *notice* abuse, not prevent it.

If integrity ever matters more than reach, the migration path is to bind votes to
paid tickets: add a nullable `sold_ticket_id` to `dj_votes` plus
`unique(round_id, sold_ticket_id)`. `sold_tickets` already holds verified
personal numbers with a `paid` status, and `/api/check-personal-number` already
exists. This is explicitly out of scope now.

## Data Model

### `djs`

| Column | Type | Notes |
|---|---|---|
| `id` | id | |
| `name` | string | Stage name — deliberately **not** localized |
| `bio` | json, nullable | Localized `ka`/`en`/`ru`/`ua`, like `partners.description` |
| `photo_id` | FK → `media`, nullable | `nullOnDelete` |
| `order` | integer, default 0 | Filament `reorderable('order')` |
| `status` | string, default `draft` | `draft` \| `published` |
| timestamps | | |

### `dj_voting_rounds`

| Column | Type | Notes |
|---|---|---|
| `id` | id | |
| `title` | string | Admin-facing label |
| `starts_at` | datetime | |
| `ends_at` | datetime | Derived from start + duration in the form |
| timestamps | | |

**No `status` column.** State is computed from `now()` against
`starts_at`/`ends_at` (`scheduled` / `open` / `closed`), so it can never drift
out of sync with the clock. The app already runs on the Tbilisi timezone.

### `dj_voting_round_dj` (pivot)

| Column | Type | Notes |
|---|---|---|
| `round_id` | FK → `dj_voting_rounds` | `cascadeOnDelete` |
| `dj_id` | FK → `djs` | `cascadeOnDelete` |
| `order` | integer, nullable | Per-round ordering override |

`unique(round_id, dj_id)`.

A global DJ list joined to rounds through a pivot (rather than DJs owning a
`round_id`) means the same DJ can appear in several rounds and be compared
across them.

### `dj_votes`

| Column | Type | Notes |
|---|---|---|
| `id` | id | |
| `round_id` | FK → `dj_voting_rounds` | `cascadeOnDelete` |
| `dj_id` | FK → `djs` | `cascadeOnDelete` |
| `voter_token` | string(64) | Opaque voter id issued by the frontend |
| `ip_hash` | string(64), nullable | `hash('sha256', ip . app.key)` — never the raw IP |
| timestamps | | |

Indexes:

- `unique(round_id, voter_token)` — **this is the deduplication guarantee.** It
  lives in the database, not in application logic, so a race between two
  simultaneous requests cannot produce two votes.
- `index(round_id, dj_id)` — supports counting.

### Invariant

**At most one round may be open at any moment.** Enforced by validation in the
Filament resource (reject a round whose `starts_at`–`ends_at` window overlaps
another round's). This is what lets the public endpoint resolve "the current
round" without a parameter.

## API (Laravel)

Both routes join the existing `locale` middleware group in `routes/api.php` and
use a new `throttle:votes` rate limiter.

### `GET /api/dj-vote`

Returns the open round, or `round: null` when none is open. A round that is
scheduled but has not started yet counts as *not* open — it returns `null`, so
nothing appears on the festival page until `starts_at` passes.

Only DJs with `status = published` are returned, and the admin form only offers
published DJs for selection. Unpublishing a DJ mid-round hides them from the
ballot; votes already cast for them are retained and still counted in the admin
statistics.

```json
{
  "round": { "id": 3, "endsAt": "2026-08-01T22:00:00+04:00" },
  "djs": [
    { "id": 7, "name": "DJ Name", "bio": "…", "photoUrl": "https://…" }
  ],
  "hasVoted": true,
  "votedDjId": 7,
  "results": [ { "djId": 7, "votes": 128, "percent": 41.2 } ]
}
```

`results` is present **only when `hasVoted` is true**. The voter is identified by
the `X-Vote-Token` request header.

### `POST /api/dj-vote`

Body `{ "djId": 7 }`. Casts or changes the vote, returns the same shape as `GET`
with `results` populated.

Errors:

- `409` — no round is open, or the round closed between page load and submit
- `422` — `djId` is not part of the open round

Writes use `updateOrCreate` keyed on (`round_id`, `voter_token`), so changing a
vote updates the existing row rather than inserting a second one.

Counting uses `withCount('votes')` over the round's DJs. At festival scale a
denormalized counter column would be premature optimisation and a source of
drift.

`percent` is the DJ's votes over the round's total votes, rounded to one
decimal. When the round has no votes at all, every `percent` is `0` — the
computation must not divide by zero, even though the public endpoint only
returns `results` after the caller has voted (so the total is at least 1 there).
The admin widget has no such guarantee and will hit the empty case.

## Cookie and Transport

The frontend runs on `:3000` and Laravel on `:8000`. A cookie set by Laravel
would be cross-site and need `SameSite=None; Secure`, which is fragile in
development and needs CORS credentials everywhere.

Instead, a new **Next.js Route Handler** at `frontend/app/api/dj-vote/route.ts`
(GET + POST) owns a first-party httpOnly cookie `dj_vote_token` (UUID, 1 year,
`SameSite=Lax`), minting it when absent. It forwards the value to Laravel as the
`X-Vote-Token` header.

Consequences:

- Laravel stays stateless and never handles a cookie — the token is just an
  opaque voter id.
- `frontend/lib/api.ts` is untouched and remains server-only.
- This is the project's first route handler under `frontend/app/api/`.

## Frontend

New client component `frontend/app/components/festival/DjVote.tsx`, mounted in
`frontend/app/dashboard/festival/page.tsx` between `FestivalHero` and
`ProductReel`.

**It renders nothing when no round is open** — matching the existing
`partners.length > 0` conditional pattern on that page.

Before voting: a grid of DJ cards (photo, name); clicking one casts the vote.
After voting: a percentage bar per DJ with vote counts, the visitor's own choice
highlighted, and the option to change it. A countdown to `endsAt` is shown while
the round is open.

The server component fetches the initial state; the client component performs
the POST and re-renders from the response.

## Admin (Filament)

### Navigation

Both resources live in a new `DJ Voting` navigation group, separate from the
existing `Content`, `Catalog`, and `Sales` groups: `Djs` (sort 1) and
`Voting rounds` (sort 2).

There is **no standalone statistics page.** Votes are always scoped to a round,
so a separate page would have to start by asking which round — the results
belong on the round's own page instead. Cross-round comparison is served by a
total-votes column on the rounds list.

### `DjResource`

`Content` navigation group, structurally mirroring `PartnerResource`:
`reorderable('order')`, photo upload through the existing
`HasContentBlocks::imageUpload` helper mapping to a `media` row, localized bio
fieldset, `status` select.

### `DjVotingRoundResource`

Form: title, DJ multi-select (writes the pivot), `starts_at`, and a **duration in
hours** field that computes `ends_at`. Row actions "Start now" and "Close now".
Validation rejects a window overlapping another round.

Table: title, computed state badge, window, total votes.

### `DjVoteResults` widget

Shown on the round's edit page, using the existing `AdminOnlyWidget` concern.
Bar chart plus a table of DJ / votes / percent, ordered by votes descending.
The rounds list carries a total-votes column.

**"Change the DJs and start over" is a new round.** Previous votes stay in the
table as history; the new round counts from zero because votes are scoped by
`round_id`.

## Error Handling

| Situation | Behaviour |
|---|---|
| Vote arrives after the round closed | `409`; the UI refetches and shows the final results |
| Two simultaneous submits, same token | `updateOrCreate` + unique index collapse them to one row |
| Missing or unrecognised token | Route handler mints a fresh UUID |
| Backend unreachable during SSR | The DJ section simply does not render |

## Testing

Feature tests (backend):

- One vote per token per round is stored
- Changing a vote updates the row instead of inserting a second one
- Voting in a closed or not-yet-started round is rejected with `409`
- Voting for a DJ outside the round is rejected with `422`
- `results` is absent before voting and present after
- A new round counts from zero while the previous round's rows survive
- Overlapping round windows are rejected, including via the "Start now" action
- Duration input produces the expected `ends_at`
- A scheduled round that has not started returns `round: null`
- Draft DJs are excluded from the ballot but their existing votes still count

Frontend tests:

The frontend currently has **no test runner** — `package.json` only defines
`dev`, `build`, `start`, and `lint`. This work adds Vitest, scoped narrowly to
the voter-token logic, which is extracted into a pure function in
`frontend/lib/vote-token.ts` so it can be tested without rendering React or
booting Next.

- `resolveVoteToken(undefined)` mints a new UUID and reports it as new
- `resolveVoteToken(existing)` returns the existing token unchanged
- A malformed token value is replaced with a fresh one

Component rendering and the route handler's HTTP wiring are covered by
`tsc --noEmit` and `next build` rather than unit tests.

## Out of Scope

- Binding votes to tickets, emails, or accounts
- Multiple simultaneous open rounds
- Ranked or multi-select ballots
- Public results before voting
- Real-time push updates (results refresh on interaction only)
