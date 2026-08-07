# Accounting page v2 — expense tracking + polish — design

## Problem

The Accounting page (`docs/superpowers/specs/2026-08-07-accounting-analytics-design.md`,
shipped in `6c47e0d`..`306ce47`) is revenue-only: gross, base, surcharge,
estimated bank fee, estimated net. The owner asked for genuinely "full"
bookkeeping — the page needs an **expense side** so the net figure reflects
real profit, plus visual/functional polish on top of the existing v1 UI.

## Decisions (locked)

| Topic | Choice |
|-------|--------|
| Scope | Add expense tracking on top of existing sales analytics (not a rewrite) |
| Expense entry | Separate Filament resource (`ExpenseResource`), manual entry — not inline on the Accounting page |
| Categories | Fixed dropdown list (not free text) |
| Event linkage | None — expenses are a flat ledger by date only, no FK to a specific event/festival |
| Net figure | Accounting hero switches to **net after expenses**; pre-expense net kept as a secondary line |
| Extra polish | Period-over-period % change badges on hero gross/net |

## Approach

Add an `expenses` table + `Expense` model + `ExpenseResource` (admin-only,
same pattern as other simple resources e.g. `PartnerResource`). Extend
`AccountingReportService` to aggregate expenses in the same date range as
sales and fold them into the summary. Add a 4th "Expenses" tab to the
existing Accounting Livewire page, and a second CSV export for expenses.

---

## 1. Data model

### `expenses` table (new migration)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `date` | date | Expense date, required |
| `category` | string | One of the fixed category slugs below |
| `amount` | decimal(10,2) | GEL, required |
| `description` | string(255) | Short title, required |
| `note` | text nullable | Free-form extra detail |
| `created_by` | foreignId nullable → `users.id`, `nullOnDelete()` | Who logged it |
| timestamps | | |

Index on `date` (range filtering, same access pattern as sold tickets/orders).

### Fixed categories

Defined as a const map on the `Expense` model (label ⇄ slug), used both by
the resource's `Select` and by report grouping:

```
venue_rent          => Venue / Rent
staff                => Staff
marketing            => Marketing
artists_talent       => Artists / Talent
equipment_production => Equipment / Production
permits_legal        => Permits / Legal
utilities            => Utilities
other                => Other
```

### Model

`App\Models\Expense` — `$fillable = [date, category, amount, description, note, created_by]`,
casts: `date` → `date`, `amount` → `decimal:2`. No soft deletes (matches
`ProductOrder`/`SoldTicket` — hard delete via resource is fine, it's a
manual ledger, not financial-record-of-truth for payments).

---

## 2. Expense management (Filament)

### `App\Filament\Resources\ExpenseResource`

- `use AdminOnlyResource;` (existing trait — `canViewAny/canCreate/canEdit/canDelete` → `isAdmin()`).
- `navigationGroup = 'Finance'` (same group as Accounting page), sort after Accounting.
- `navigationIcon = 'heroicon-o-banknotes'`.
- Form: `DatePicker(date)` required default today, `Select(category)` required
  from the fixed map, `TextInput(amount)->numeric()->required()`,
  `TextInput(description)->required()->maxLength(255)`, `Textarea(note)`.
  `created_by` set from `auth()->id()` in `CreateExpense` page (`mutateFormDataBeforeCreate`),
  not user-editable.
- Table columns: date (sortable, default sort desc), category (badge), description,
  amount (right-aligned, GEL), note (toggleable, truncated), created_by.name
  (toggleable). Filters: category select, date range filter (Filament's
  built-in date range or two `Filter` instances — consistent with how the
  Accounting page already does from/to).
- Standard `EditAction` + `DeleteBulkAction`, no custom pages beyond the
  default List/Create/Edit trio (matches `PartnerResource` shape).

---

## 3. Accounting page integration

### `AccountingReportService`

- New private loader `loadExpenses(Carbon $from, Carbon $to): Collection`
  — `Expense::whereBetween('date', [$from->toDateString(), $to->toDateString()])->get()`.
  (Expenses are not filtered by the `channel` filter — that concept doesn't
  apply to expenses.)
- `bundle()` gains:
  - `summary.total_expenses` = `round(sum(amount), 2)`
  - `summary.net_after_expenses` = `summary.estimated_net - summary.total_expenses`
  - `byExpenseCategory`: map of category slug → `{label, total, count}` for
    all 8 categories (zero-filled for categories with no rows, so the chart
    always renders a stable legend).
- `csvRowsExpenses(Carbon $from, Carbon $to): iterable` — one row per expense:
  `date, category, description, amount, note, created_by`.
- Existing `summary()`, `breakdownByKind()`, etc. are unaffected — this is
  additive.

### `Accounting` page (Livewire)

- `activeTab` gains a 4th value `'expenses'`; tab bar adds "Expenses" label.
- Hero card: primary number becomes **Net after expenses**; a line below
  shows "Pre-expense net {X} − expenses {Y}" (mirrors the existing
  "Gross − bank estimate" subline style already on the card).
- New Expenses tab:
  - Doughnut chart "Expenses by category" (same Chart.js pattern/colors as
    the existing Charts tab), fed by `byExpenseCategory`.
  - Total expenses stat tile for the period.
  - Plain table of expense rows in range: date, category, description,
    amount, note — sorted newest first, no pagination needed for v1 (same
    simplicity level as the existing breakdown tables; add pagination later
    if a period realistically has >~50 rows).
  - "+ Add expense" button (`x-filament::button` styled like existing
    buttons) linking to `ExpenseResource::getUrl('create')`.
- CSV export: existing `exportCsv()` action/button relabeled **"Export sales
  CSV"**. New `exportExpensesCsv()` action + **"Export expenses CSV"**
  button on the Expenses tab, streaming `csvRowsExpenses()` with headers
  `date, category, description, amount, note, created_by`.

### Period-over-period badges

- For the current `[from, to]` range, compute the immediately preceding
  range of equal length: `prevTo = from->copy()->subDay()`,
  `prevFrom = prevTo->copy()->subDays($from->diffInDays($to))`.
- Reuse `AccountingReportService::summary()` against that shifted range
  (same channel filter) to get `prevGross` / `prevNetAfterExpenses`.
- Badge = `round((current - previous) / previous * 100)`; render as
  `+12%` (emerald) / `-8%` (rose) next to Gross and Net after expenses on
  the hero/overview cards. If `previous` is 0, hide the badge (avoid
  divide-by-zero / meaningless infinite%).
- This is display-only — no new stored data, no change to filters.

### Out of scope (unchanged from v1 + explicitly excluded here)

- Partner 50/50 split, real bank settlement import, invoices/tax docs.
- Recurring/templated expenses, receipt/attachment uploads.
- Linking an expense to a specific event/festival.
- Editing categories from the UI (fixed list, code-defined for v1).

---

## 4. Testing

- Unit: `AccountingReportServiceTest` — expense aggregation sums correctly
  per category and total; `net_after_expenses` math; zero-filled categories
  with no rows; expenses outside the date range excluded.
- Feature: `ExpenseResource` CRUD is admin-only (403/redirect for non-admin,
  same pattern as existing `AccountingPageTest`); creating an expense sets
  `created_by` from the authenticated admin.
- Feature: Accounting page renders `net_after_expenses` reflecting seeded
  expenses; Expenses tab lists seeded rows; expenses CSV export contains
  expected columns/rows for a seeded expense.
- Unit: period-over-period % change helper — normal case, previous-period-zero
  case (no divide-by-zero), single-day range.

---

## 5. Rollout

1. Migration (`expenses` table) + `Expense` model + `ExpenseResource`.
2. `AccountingReportService`: expense aggregation, `net_after_expenses`,
   `byExpenseCategory`, `csvRowsExpenses`.
3. Accounting page: Expenses tab, hero card update, expenses CSV button,
   period-over-period badges.
4. Tests (unit + feature) per section 4.
5. Manual smoke test: seed a couple of expenses across categories, confirm
   hero net drops by the expense total, category chart renders, both CSV
   exports download with correct rows.
