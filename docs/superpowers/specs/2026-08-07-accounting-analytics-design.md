# Online payment surcharge + admin accounting analytics — design

## Problem

1. **Bank fees eat margin.** ProCredit charges ~1.7% on ProCredit cards and
   ~2.5% on other/foreign cards. Today Quipu is charged at catalog price only,
   so the bank fee comes out of festival revenue. Owners want the **buyer** to
   cover a safety-buffered fee (like other ticket sellers: price + fee).
2. **No bookkeeping view.** Admins (two partners) need a clear admin page:
   what sold, how much, which types, online vs walk-up, collected surcharge,
   estimated bank take, and estimated net — plus CSV export. Partner 50/50
   split is **out of scope** (they allocate net offline).

## Decisions (locked)

| Topic | Choice |
|-------|--------|
| Buyer surcharge | **+3%** on online (Quipu) checkout only |
| Why 3% | Safety buffer above bank max (~2.5%) |
| Walk-up / New Sale | **No** surcharge (`surcharge_amount = 0`) |
| Checkout UI | Buyer sees **final total only** (fee not itemized) |
| Partner split | **Not** in analytics |
| Analytics | Summary cards + breakdowns + **CSV export** |
| Access | **Admin only** |
| Rate storage | Persisted per order + editable default in Site Settings |

## Approach

Persist fee fields on each paid order at creation time; charge Quipu the
gross `amount`. Build one Filament **Accounting** page that aggregates from
those fields and exports CSV. Do not invent partner payouts or bank settlement
imports.

---

## 1. Data model

### Columns on `sold_tickets` and `product_orders`

| Column | Type | Meaning |
|--------|------|---------|
| `base_amount` | decimal(10,2) | Catalog / list price before surcharge (after walk-up discount if any) |
| `surcharge_amount` | decimal(10,2) | Fee added for the buyer (0 for walk-up) |
| `surcharge_rate` | decimal(5,2) nullable | Percent used at sale time (e.g. `3.00`); null/0 when no surcharge |
| `amount` | existing | **Gross charged / collected** = base + surcharge (unchanged semantic for callbacks) |

Invariant for new online sales:

```
amount = base_amount + surcharge_amount
surcharge_amount = round(base_amount * surcharge_rate / 100, 2)
```

Rounding: standard half-up to 2 decimal places in GEL (same as existing money
handling). Quipu and callback verification use **`amount` only**.

### Backfill (migration)

For existing rows:

- `base_amount = amount`
- `surcharge_amount = 0`
- `surcharge_rate = null`

Do **not** retroactively invent a 3% fee on historical orders.

### Site setting

Key: `payment_surcharge_percent` (JSON/site_settings), default **`3`**.

- Admin-editable on Site Settings (payments section or numeric field).
- Read at **order creation** only; changing the setting does not rewrite past
  orders (each row keeps its `surcharge_rate` / amounts).

### Estimated bank fee (analytics only — not stored)

Conservative estimate for online (`pg_order_id` present or `sold_by` null):

```
estimated_bank_fee = amount * 0.025
```

Walk-up: `estimated_bank_fee = 0`.

```
estimated_net = amount - estimated_bank_fee
```

If later ProCredit returns actual MDR per transaction, analytics can switch to
stored actuals; until then 2.5% is the documented conservative assumption.

---

## 2. Checkout / payment flow

### Online (tickets & products)

1. Frontend continues to show a **single total** (no “+3% fee” line).
2. API / `CreateTicketOrderAction` / `CreateProductOrderAction`:
   - Resolve catalog price (existing rules).
   - Read `payment_surcharge_percent` from Site Settings (fallback `3`).
   - Set `base_amount`, `surcharge_rate`, `surcharge_amount`, `amount`.
   - Create Quipu order with **`amount`** (gross).
3. `ProcessPaymentCallbackAction` / `verifyPaidAmount`: compare gateway paid
   amount to stored **`amount`** (gross), unchanged pattern.
4. Public pricing used by Next.js must return the **gross payable total**
   (base + surcharge) as the amount the buyer pays. Buyer UI remains a
   single number with no fee line item.

### Walk-up (`CreateWalkUpTicketSaleAction` / `CreateWalkUpProductSaleAction`)

- `base_amount = amount` (post-discount collected cash).
- `surcharge_amount = 0`, `surcharge_rate = null`.
- No Quipu call (unchanged).

### Frontend display

- Show only the final payable GEL.
- Do not label or break out the 3% on the storefront (decision B).

---

## 3. Accounting page (Filament)

### Placement & access

- New Filament page, e.g. **Accounting** / **ბუღალტერია**, nav group Finance
  or Reports.
- `canAccess()` → `User::isAdmin()` only (`AdminOnly` pattern).
- Scanner / seller / editor: hidden.

### Filters

- Date range: presets (today / week / month) + custom from–to.
- Channel: all / online / walk-up.
  **Rule:** `sold_by IS NULL` → online; `sold_by IS NOT NULL` → walk-up.
  (Online checkout never sets `sold_by`; New Sale always sets it.)

Date basis:

- Tickets: `paid_at` (fallback `created_at` if null).
- Products: **add nullable `paid_at`** on `product_orders`, set on Quipu
  callback and on walk-up create (same as tickets). Backfill existing paid
  product rows with `paid_at = updated_at` (or `created_at` if that is more
  accurate for the row). Accounting filters both tables by `paid_at`.

### Summary cards (filtered period)

1. Gross collected — `sum(amount)`
2. Base revenue — `sum(base_amount)`
3. Surcharge collected — `sum(surcharge_amount)`
4. Estimated bank fee — online `sum(amount * 0.025)`, walk-up 0
5. Estimated net — gross − estimated bank fee
6. Counts — paid tickets count, paid product orders count

### Breakdowns

- Tickets vs products (gross / base / surcharge / counts).
- Ticket types: joker / techno / standard (from `is_joker` / `is_techno`).
- By day (table or simple chart optional; table is enough for v1).

### CSV export

- Same filters as the page.
- One row per paid sale (ticket or product), columns at least:
  type, id, paid_at, channel, event/product title, base_amount,
  surcharge_rate, surcharge_amount, amount, estimated_bank_fee, buyer email
  (optional), sold_by.
- Optional footer/summary rows or a second summary sheet is nice-to-have;
  one flat CSV is sufficient.

### Out of scope

- Partner 50/50 split widgets
- Importing real ProCredit settlement files
- Invoices / tax documents
- Itemizing surcharge on the public checkout UI
- Changing walk-up pricing

---

## 4. Testing

- Unit/feature: surcharge math rounding; online order persists fields and
  Quipu amount = gross; walk-up surcharge 0; callback verifies gross;
  setting change does not alter existing rows; Accounting page admin-only;
  CSV contains expected columns for a seeded paid ticket + product.
- Backfill migration leaves historical `amount` unchanged.

---

## 5. Rollout

1. Migration + model fillable/casts + Site Settings field.
2. Wire create-order + walk-up actions + product `paid_at`.
3. Frontend total = gross (still single number).
4. Filament Accounting page + CSV.
5. Deploy; smoke-test one Quipu sandbox/live purchase shows 103 on a 100 GEL
   catalog ticket when rate is 3%.
