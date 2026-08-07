# Admin roles: ticket scanner staff + seller POS — design

## Problem

The Filament admin panel has exactly one shared login (`admin@tbilisistyle.ge`)
and a `users.role` column that only distinguishes `admin`/`editor`. Two new
operational needs for the festival:

1. **~30 door staff** need to scan ticket QR codes and mark them redeemed.
   They must see *only* the scanner page — nothing else in the admin panel
   (content, orders, financials).
2. **In-person sellers** need to record a walk-up sale (cash/card taken
   outside the site) at a discount, attributed to themselves, without going
   through the Quipu online checkout.

Today, `ValidateTicketAction` also hardcodes `scanned_by = 'admin'` for every
scan — with 30 people scanning, there's no way to tell who scanned what.

## Roles

`users.role` gets two new values, alongside the existing two:

| role      | sees                                    |
|-----------|------------------------------------------|
| `admin`   | everything (unchanged)                   |
| `editor`  | content resources (unchanged)            |
| `scanner` | **only** the Ticket Scanner page         |
| `seller`  | **only** the New Sale (POS) page         |

One role per user — no multi-role/permission-matrix. `User::isAdmin()` already
exists as the pattern; add `isScanner()` and `isSeller()` alongside it.

Filament page/resource visibility is gated via `canAccess()` overrides
(`TicketScanner::canAccess()` → `isAdmin() || isScanner()`, `NewSale::canAccess()`
→ `isAdmin() || isSeller()`), matching the existing `AdminOnlyResource` trait
pattern already used by `SoldTicketResource` etc.

## User management (`UserResource`)

New Filament resource, admin-only (`AdminOnlyResource` trait), under a new
"Team" nav group:

- Table: name, email, role badge, created_at.
- Create/Edit form: name, email, password (create only), role select
  (admin/editor/scanner/seller).
- Table action **"Generate scanner accounts"**: prompts for a count N,
  creates `scanner01@tbilisistyle.ge` … `scannerNN@tbilisistyle.ge` (next
  available number, won't collide with existing ones) with random 8-char
  passwords and `role=scanner`. Shows a modal listing the generated
  email/password pairs (Filament `Notification`/`Action::modalContent`) so
  the admin can copy/print and hand them out — passwords are never shown
  again after generation, matching normal account-creation security.

## Scanner accountability

`ValidateTicketAction::execute()` currently writes `'scanned_by' => 'admin'`
unconditionally. Change to the authenticated user's name:
`auth()->user()?->name ?? 'unknown'`. `SoldTicketResource`'s table already has
a `scanned_by` column, so this is visible to admins with zero UI work.

## Seller POS flow (`NewSale` page)

New Filament page, `seller`+`admin` access. Form:

- Type: Ticket or Product (radio/select)
- Item: dependent select (active tickets, or products+size)
- Buyer: name, surname, personal number, email
- Discount: amount in GEL (simple subtraction from list price; not a %,
  keeps the math and the audit trail unambiguous)

On submit, a new `CreateWalkUpSaleAction` (mirrors `CreateTicketOrderAction`/
`CreateProductOrderAction` but skips the gateway entirely):

1. Lock + validate the ticket/product size has stock (same checks as the
   online path).
2. Create `SoldTicket`/`ProductOrder` directly with `status='paid'`,
   `paid_at=now()`, `pg_order_id=null`, plus two new columns:
   - `sold_by` (nullable string — seller's name, null = normal online sale)
   - `discount_amount` (nullable decimal, GEL)
   `amount` stores the **final, post-discount** price actually collected.
3. Decrement `tickets.quantity` / `product_sizes.quantity` (same as
   `ProcessPaymentCallbackAction` does for online payments).
4. Generate the QR via the existing `QrCodeService::generateTicketData()` —
   identical signed payload, so it scans exactly like an online-bought
   ticket.
5. Dispatch the **existing** `SendTicketEmailJob` / `SendProductOrderEmailJob`
   — the buyer gets their QR by email exactly like an online purchase.
   No new email code.

## Data model changes (additive only)

```
sold_tickets:    + sold_by (nullable string), + discount_amount (nullable decimal 10,2)
product_orders:  + sold_by (nullable string), + discount_amount (nullable decimal 10,2)
```

Both nullable, both default null — existing rows and the online purchase
path are untouched. Matches this project's additive-migration convention
(see [[festival-redesign-preview]]).

## Out of scope (explicitly deferred)

- Per-seller sales reporting/commission widgets — the raw data (`sold_by`,
  `discount_amount`) is there to query later; no new dashboard widget now.
- Editing/voiding a POS sale after creation — use the existing
  `SoldTicketResource`/`ProductOrderResource` edit screens (admin-only)
  if a correction is needed.
- Rate-limiting or capping how many walk-up sales one seller can create.

## Testing

- Feature test: scanner-role user gets 403 on any non-scanner admin route;
  can reach and use `/admin/ticket-scanner`.
- Feature test: seller-role user gets 403 outside `/admin/new-sale`.
- Feature test: `ValidateTicketAction` records the acting user's name in
  `scanned_by`.
- Feature test: `CreateWalkUpSaleAction` creates a paid `SoldTicket` with
  correct discounted `amount`, decrements ticket quantity, dispatches the
  email job.
