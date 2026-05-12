# Sales Invoice App

A simple Laravel + Livewire 4 sales invoice application: maintain customers and items, draft sales invoices with multiple lines, then **post** an invoice — which copies the header and lines into immutable `posted_sales_*` tables and marks the source invoice as posted.

Built on the `laravel/livewire-starter-kit` (Laravel 13 + Livewire 4 + Flux 2 + Fortify).

---

## Quick start

```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
composer run dev
```

`composer run dev` starts the PHP server, queue worker, log tail, and Vite in parallel.

Visit <http://127.0.0.1:8000> and log in with:

| Email | Password |
| --- | --- |
| `test@example.com` | `password` |

The login page also surfaces these credentials in non-production environments.

---

## Domain model

Six tables. The first four are operational; the last two are the immutable "posted" copies.

| # | Table | Purpose |
|---|---|---|
| 1 | `customers` | Customer master |
| 2 | `items` | Item / product master with unit price + UoM |
| 3 | `sales_headers` | Draft / released / cancelled invoices |
| 4 | `sales_lines` | Lines belonging to a `sales_header` |
| 5 | `posted_sales_headers` | Snapshot of a posted invoice header (with `customer_no` and `customer_name` denormalised) |
| 6 | `posted_sales_lines` | Snapshot lines (with `item_no` denormalised) |

See [`db_structure.md`](db_structure.md) for the full column-by-column schema.

### Sales header statuses

```
draft → released → posted
                 ↘ cancelled
```

- `draft` / `released` invoices are editable.
- `posted` invoices are immutable — the UI hides Save/Post and surfaces the data read-only.
- `cancelled` invoices cannot be posted.

### Posting flow

1. User creates a `SalesHeader` with status `draft`.
2. User adds `SalesLine`s — `line_amount` is auto-derived (`qty * price + tax - discount`) on save.
3. User clicks **Post invoice**.
4. `App\Actions\Sales\PostSalesInvoice::handle()` runs in a DB transaction:
   - Recalculates the header totals from its lines.
   - Creates a `PostedSalesHeader` with a generated `posted_invoice_no` (`PSI-yyyymmdd-XXXXX`), denormalising the customer name and number.
   - Copies each `SalesLine` into a `PostedSalesLine`, denormalising the item number.
   - Updates the source header's `status` to `posted`.
5. The user is redirected to the read-only posted-invoice detail page.

The action refuses to post:
- An invoice that is already `posted`.
- A `cancelled` invoice.
- An invoice with no lines.

---

## Application layout

| URL | Page | Component |
| --- | --- | --- |
| `/dashboard` | Dashboard | `dashboard` (plain view) |
| `/customers` | Customer list (search + pagination) | `pages::customers.index` |
| `/customers/create`, `/customers/{customer}/edit` | Customer form | `pages::customers.edit` |
| `/items` | Item list | `pages::items.index` |
| `/items/create`, `/items/{item}/edit` | Item form | `pages::items.edit` |
| `/sales` | Sales invoice list (with status filter) | `pages::sales.index` |
| `/sales/create`, `/sales/{sale}/edit` | Invoice editor with line repeater + **Post** | `pages::sales.edit` |
| `/sales-posted` | Posted invoice list | `pages::sales-posted.index` |
| `/sales-posted/{postedSale}` | Posted invoice (read-only) | `pages::sales-posted.show` |

All routes are gated by `auth` + `verified` middleware (see `routes/web.php`).

Livewire pages live in `resources/views/pages/` as single-file Volt-style components — class definition at the top of the Blade file, single root element in the template, layout auto-applied from `resources/views/layouts/app.blade.php`.

---

## Seeded fixtures

`database/seeders/DatabaseSeeder.php` creates:

- **1 user** — `test@example.com` / `password`
- **4 customers** — `C-0001` … `C-0004`
- **5 items** — `I-0001` … `I-0005`
- **3 sales invoices** — `SI-0001` (draft, 3 lines, total 18,476), `SI-0002` (released, 2 lines, total 108,564), `SI-0003` (draft, 1 line, total 21,750)

Re-run with `php artisan migrate:fresh --seed`. The seeder uses `updateOrCreate` so it is idempotent if the tables already exist.

---

## Tests

Run the full suite:

```powershell
php artisan test
```

Run only the sales-invoice tests:

```powershell
php artisan test --filter=Sales
```

Tests use:

- **SQLite in-memory** (`DB_DATABASE=:memory:`) — fast, isolated; see `phpunit.xml`.
- **`MAIL_MAILER=log`** with `MAIL_LOG_CHANNEL=null` — mail is routed through the log mailer but discarded by the `null` log channel, so no real emails are sent and the test output isn't polluted.
- **`RefreshDatabase`** trait — fresh schema per test class.

### Test coverage

| File | What it covers |
| --- | --- |
| `tests/Feature/Sales/SalesLineTotalsTest.php` | `line_amount` auto-calc on `SalesLine::saving`; `SalesHeader::recalculateTotals()` |
| `tests/Feature/Sales/PostSalesInvoiceTest.php` | Header + line snapshotting, status flip, refusal cases (already posted, no lines), atomicity |
| `tests/Feature/Sales/CustomerPagesTest.php` | Livewire CRUD + uniqueness validation |
| `tests/Feature/Sales/ItemPagesTest.php` | Livewire CRUD |
| `tests/Feature/Sales/SalesInvoicePageTest.php` | Invoice page mount, item auto-fill, save/post happy paths, posted-show render |

20 tests, 57 assertions at time of writing.

---

## Project structure (relevant additions)

```
app/
  Actions/Sales/PostSalesInvoice.php
  Models/Customer.php
  Models/Item.php
  Models/SalesHeader.php
  Models/SalesLine.php
  Models/PostedSalesHeader.php
  Models/PostedSalesLine.php
database/
  migrations/2026_05_12_100000_create_customers_table.php
  migrations/2026_05_12_100001_create_items_table.php
  migrations/2026_05_12_100002_create_sales_headers_table.php
  migrations/2026_05_12_100003_create_sales_lines_table.php
  migrations/2026_05_12_100004_create_posted_sales_headers_table.php
  migrations/2026_05_12_100005_create_posted_sales_lines_table.php
  seeders/DatabaseSeeder.php
resources/views/pages/
  customers/{index,edit}.blade.php
  items/{index,edit}.blade.php
  sales/{index,edit}.blade.php
  sales-posted/{index,show}.blade.php
routes/web.php
tests/Feature/Sales/*.php
```

---

## Troubleshooting

- **`Maximum execution time of 30 seconds exceeded`** — stale view/route cache. Run `php artisan optimize:clear` and reload.
- **`MultipleRootElementsDetectedException`** — a Livewire page has more than one top-level Blade element. Each `pages/*.blade.php` file must have exactly one root `<div>` / `<section>`; the surrounding layout is auto-applied.
- **`Class not found` for a new model** — run `composer dump-autoload`.
