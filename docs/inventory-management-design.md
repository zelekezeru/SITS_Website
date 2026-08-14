# SITS Inventory & Asset Management (the "Store" module)

Design and delivery plan for the third ERP pillar, alongside People/Performance and
Finance. Scope: every physical thing the Seminary owns — from a box of chalk to the
minibus — registered once, tracked continuously, and reconciled against a countable
ledger.

Status: **Phases 0–2 implemented** (access layer, foundation, catalog & receiving).
Phases 3–6 specified below.

---

## 1. The modelling decision that drives everything

A seminary owns two categorically different kinds of property, and the single most
common way inventory systems fail is forcing both into one table with a `quantity`
column:

| | **Consumable stock** | **Fixed asset** |
|---|---|---|
| Examples | chalk, A4 paper, detergent, communion supplies, fuel | laptops, projectors, desks, the minibus, keyboards (musical) |
| Identity | fungible — 500 pens are interchangeable | individual — *that* laptop, serial `C02X…` |
| Question asked | "how many are left, and where?" | "who has it, what condition, when serviced?" |
| Depletes | yes, on issue | no — it returns, or is disposed of |
| Needs | reorder level, valuation, expiry | asset tag, custody chain, depreciation, maintenance |

A `quantity` column cannot answer "which laptop is with the Registrar." An asset
register cannot sanely hold 500 pens. So the schema splits the *definition* of a
thing from its *physical instances*:

```
                    ┌─────────────────────┐
                    │ inventory_items     │  the catalog entry ("Dell Latitude 5420",
                    │  tracking_mode      │   "A4 Paper 80gsm") — name, category,
                    └──────────┬──────────┘   unit of measure, reorder policy, photos
                               │
              tracking_mode = ASSET │ CONSUMABLE
                    ┌──────────┴──────────┐
                    ▼                     ▼
        ┌────────────────────┐  ┌──────────────────────────┐
        │ inventory_units    │  │ (no per-unit rows)       │
        │  asset_tag, serial │  │  quantity lives only in  │
        │  condition, status │  │  the movement ledger     │
        │  assigned_to       │  └──────────────────────────┘
        └─────────┬──────────┘             │
                  └───────────┬────────────┘
                              ▼
              ┌───────────────────────────────────┐
              │ inventory_stock_movements         │  ← THE source of truth
              │  signed quantity, type, locations │
              └───────────────────────────────────┘
```

### Quantity is never stored — it is summed

`available_quantity` is **derived** from the movement ledger, exactly as
`EmployeeLoan::balance` is derived from its payment ledger (`app/Models/EmployeeLoan.php`).
This is already the house pattern and it is the right one here: a receipt, an issue, a
return, a transfer, a stocktake variance and a write-off all reconcile to one number
that cannot drift out of sync with its own history.

> `on_hand(item, location) = Σ inventory_stock_movements.quantity`

A `inventory_stock_balances` cache table is deliberately **deferred** to Phase 6, and
only if measured queries justify it. Correct first, fast second.

### Purchase facts belong to the receipt, not the item

"Quantity purchased", "purchase date", "supplier", "received by", "registered by" are
attributes of *one buying event* — an item bought three times has three of each. They
live on `inventory_batches` (goods-received notes), not on the item. This also gives
FIFO costing, expiry tracking per lot, and per-supplier performance for free.

---

## 2. Schema

Fourteen tables, all prefixed `inventory_` to stay clear of the Library ILS (which
already owns `Stocktake`, `Loan`, `Category`, `Transfer`, and the
Floor→Row→ShelfBox hierarchy — see §7).

### Catalog

**`inventory_categories`** — self-referencing tree (`Furniture → Chairs → Stackable`).
`parent_id`, `name_en`, `name_am`, `code`, `tracking_mode` (the default new items
inherit), `default_depreciation_method`, `default_useful_life_months`, `is_active`.

**`inventory_items`** — the catalog record.
`code` (SKU, unique, auto-generated), `name_en`, `name_am`, `description`,
`category_id`, `tracking_mode`, `unit_of_measure`, `brand`, `model`, `specification`,
`reorder_level`, `reorder_quantity`, `standard_unit_cost`, `is_consumable_expiring`,
`primary_image_id` → `documents.id`, `status`, `is_active`, `notes`,
`created_by`, timestamps, soft deletes.

**`inventory_suppliers`** — `name`, `code`, `tin` (Ethiopian tax id), `contact_person`,
`phone`, `email`, `address`, `city`, `bank_account`, `rating` (1–5), `is_active`,
`notes`. Referenced by batches; drives the supplier-performance report.

**Images & documents** reuse the existing polymorphic `documents` table
(`documentable_type = App\Models\InventoryItem`) with `category` distinguishing
`image` / `invoice` / `warranty` / `manual`. `inventory_items.primary_image_id` pins
the list thumbnail. No new attachment table, no new uploader — `App\Support\DocumentUploader`
already handles this, including the uploader audit trail.

### Space

**`inventory_locations`** — hierarchical and generic: `campus_id` → `Campus` (reused,
not duplicated), `parent_id`, `type` (`store`, `building`, `room`, `shelf`, `bin`,
`office`, `vehicle`), `name`, `code`, `barcode`, `custodian_employee_id`,
`is_issuable`, `is_active`. A full path renders as
`Main Campus → Central Store → Room 12 → Shelf B → Bin 3`, which satisfies the
"Room, shelf name, location" requirement at any depth without three fixed columns.

### Receiving

**`inventory_batches`** — one row per goods-received event (GRN).
`grn_number` (unique, `GRN-2026-0043`), `item_id`, `supplier_id`, `quantity_received`,
`unit_cost`, `currency`, `total_cost`, `purchase_date`, `production_date` (nullable),
`expiry_date` (nullable), `warranty_until`, `invoice_number`, `purchase_order_number`,
`received_by` (employee), `registered_by` (user), `location_id`, `condition_on_arrival`,
`notes`, timestamps.

### Assets

**`inventory_units`** — one row per serialized asset instance.
`item_id`, `batch_id`, `asset_tag` (unique, QR-printable — `SITS-IT-000871`),
`serial_number`, `status` (`in_store`, `issued`, `in_use`, `under_maintenance`,
`reserved`, `lost`, `disposed`), `condition` (`new` … `unserviceable`),
`current_location_id`, `assigned_to_employee_id`, `assigned_at`, `purchase_cost`,
`depreciation_method`, `useful_life_months`, `salvage_value`, `warranty_until`,
`last_maintenance_at`, `next_maintenance_due_at`, `notes`, soft deletes.

**`inventory_asset_assignments`** — the custody ledger (answers "borrowed by, if any"
with *history*, not just a current pointer).
`unit_id`, `employee_id`, `issued_at`, `due_at`, `returned_at`, `condition_out`,
`condition_in`, `issued_by`, `received_back_by`, `purpose`, `acknowledgement_path`
(signed handover slip), `notes`. `assigned_to_employee_id` on the unit is a
denormalized convenience pointer maintained by the service layer.

**`inventory_maintenance_logs`** — `unit_id`, `type` (`preventive`, `repair`,
`calibration`, `inspection`), `reported_by`, `vendor_name`/`supplier_id`, `cost`,
`started_at`, `completed_at`, `next_due_at`, `downtime_days`, `outcome`, `notes`.

**`inventory_disposals`** — `unit_id` (or `item_id` + quantity for consumables),
`method` (`sold`, `donated`, `scrapped`, `written_off`, `lost`, `returned_to_supplier`),
`reason`, `book_value`, `proceeds`, `requested_by`, `approved_by`, `approved_at`,
`status`, `document_path`. Approval is a **separate permission** — see §3.

### Movement & flow

**`inventory_stock_movements`** — the ledger. Append-only; corrections are new rows,
never edits.
`item_id`, `unit_id` (nullable), `batch_id` (nullable), `type`, `quantity` (signed:
`+` in, `−` out), `from_location_id`, `to_location_id`, `employee_id` (counterparty),
`request_id`, `reference` (voucher no.), `unit_cost`, `occurred_at`, `performed_by`,
`reason`, `notes`. Indexed on `(item_id, occurred_at)` and `(to_location_id, item_id)`.

**`inventory_requests` + `inventory_request_lines`** — the requisition workflow, using
the maker-checker shape the ERP already speaks (KPIs, mass permissions, medical
allowance all work this way):

```
  Employee            Dept Head / Ops        Store Keeper
  ────────            ───────────────        ────────────
  draft ──submit──▶ submitted ──approve──▶ approved ──issue──▶ fulfilled
                        │                                  └─▶ partially_fulfilled
                        └──reject──▶ rejected                       │
                                                              cancelled
```
`request_number` (`REQ-2026-0912`), `requested_by_employee_id`, `department_id`,
`purpose`, `needed_by`, `status`, `submitted_at`, `approved_by`, `approved_at`,
`rejection_reason`, `fulfilled_at`, `issued_by`. Lines carry
`item_id`, `quantity_requested`, `quantity_approved`, `quantity_issued`, `unit_id`
(for a specific asset), `note`.

**`inventory_stocktakes` + `inventory_stocktake_lines`** — physical count sessions.
`reference`, `location_id`, `scope`, `status` (`open`, `counting`, `review`,
`posted`, `cancelled`), `started_by`, `started_at`, `posted_by`, `posted_at`.
Lines: `item_id`, `unit_id`, `system_quantity` (snapshot), `counted_quantity`,
`variance`, `variance_reason`, `counted_by`. Posting a stocktake writes
`adjustment` movements — and **posting requires a different permission than counting**.

---

## 3. Access model — roles, permissions, segregation of duties

One new role: **Store Keeper**. Oversight permissions go to roles that already exist,
so maker-checker is satisfiable without inventing a second store role (the same
approach that gave mass permissions a second authoriser).

Permission names follow the ERP convention (space-separated strings, seeded by
`RolesAndPermissionsSeeder`); they are enumerated in `App\Enums\StorePermission` so no
call site hardcodes a literal.

| Permission | Store Keeper | Ops Manager | President | Finance | Dept Head | Employee |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| `view inventory` | ✓ | ✓ | ✓ | ✓ | ✓ | |
| `manage inventory catalog` | ✓ | ✓ | ✓ | | | |
| `manage inventory locations` | ✓ | ✓ | ✓ | | | |
| `manage inventory suppliers` | ✓ | ✓ | ✓ | | | |
| `receive inventory` | ✓ | ✓ | ✓ | | | |
| `issue inventory` | ✓ | | ✓ | | | |
| `transfer inventory` | ✓ | ✓ | ✓ | | | |
| `manage inventory assets` | ✓ | ✓ | ✓ | | | |
| `conduct inventory stocktake` | ✓ | ✓ | ✓ | | | |
| `request inventory` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `view own inventory requests` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `approve inventory requests` | | ✓ | ✓ | | ✓ | |
| `adjust inventory` | | ✓ | ✓ | | | |
| `approve inventory disposal` | | ✓ | ✓ | | | |
| `view inventory reports` | ✓ | ✓ | ✓ | ✓ | | |

Three deliberate **denials** for the Store Keeper — this is the control that makes the
module auditable rather than merely convenient:

1. **No `approve inventory requests`.** The custodian of the goods cannot authorise
   their own release.
2. **No `adjust inventory`.** A store keeper *counts* (`conduct inventory stocktake`)
   and records variance; posting that variance to stock — which is how shrinkage gets
   quietly erased — requires Operations or the President.
3. **No `approve inventory disposal`.** Writing an asset off is the classic loss
   vector; it needs a second signature.

Finance gets read + reports only: they need valuation and cost data, not stock control.

### Routing surface

There is **one** store UI, at `/store/*`. The President's admin sidebar links to the
very same routes rather than duplicating pages under `/admin/inventory` — so there is
one controller, one Vue page and one permission check per capability. Convention
followed:

- `/store` (the dashboard/landing) → `role.landing:Store Keeper`, which gracefully
  bounces a non-store user to *their* dashboard instead of a hard 403, and lets
  President / SUPERADMIN through (`App\Http\Middleware\EnsureRole`).
- every other `/store/*` route → `can:<permission>`, so a user with the permission but
  not the role (Ops Manager, Finance) reaches exactly the pages they're entitled to.

`RoleLanding::MAP` places `Store Keeper` between `Finance Officer` and
`Department Head`: a dedicated store staffer lands on `/store`, and because the sub-pages
are permission-gated rather than role-gated, someone whose store duty is secondary can
be granted the permissions without losing their primary portal.

The sidebar (`App\Support\StoreNavigation`) filters its own items by permission
server-side, so no user is shown a link they'd be bounced from.

---

## 4. Identifiers, codes and QR

Human-meaningful, sortable, collision-free, generated in a transaction:

| Thing | Format | Example |
|---|---|---|
| Item SKU | `<CAT>-<seq:5>` | `IT-00184` |
| Asset tag | `SITS-<CAT>-<seq:6>` | `SITS-IT-000871` |
| GRN | `GRN-<year>-<seq:4>` | `GRN-2026-0043` |
| Requisition | `REQ-<year>-<seq:4>` | `REQ-2026-0912` |
| Issue voucher | `ISV-<year>-<seq:4>` | `ISV-2026-0455` |
| Stocktake | `STK-<year>-<seq:3>` | `STK-2026-007` |
| Location barcode | `LOC-<campus>-<seq:4>` | `LOC-MC-0031` |

Asset tags and location codes render as QR labels for printing; the module reuses the
`qr-reader` bundle already committed in `public/build` for the library, so a phone
camera can pull up an asset or count a shelf without new dependencies.

---

## 5. Invariants the service layer must guarantee

1. Stock can never go negative — an issue is rejected if
   `on_hand(item, from_location) < quantity`, checked inside the transaction that
   writes the movement.
2. An `asset`-mode item never gets a bare quantity movement; every movement carries a
   `unit_id`.
3. A unit is in exactly one location and assigned to at most one employee at a time;
   issuing an already-issued unit fails.
4. Movements are append-only. A mistake is corrected by a compensating movement with a
   `reason`, so the audit trail shows the error *and* the correction.
5. `quantity_issued ≤ quantity_approved ≤ quantity_requested` on every request line.
6. Posting a stocktake is idempotent — a posted stocktake cannot be re-posted.
7. Disposal requires an approval record; a disposed unit accepts no further movements.

All mutating models use `LogsOperationalActivity` (Spatie activitylog), so the existing
`/admin/audit` view covers inventory with no extra work.

---

## 6. Reports (Phase 5)

- **Stock on hand** — by item / category / location, with valuation (FIFO from batches).
- **Reorder alert** — `on_hand ≤ reorder_level`, the store dashboard's primary tile.
- **Slow / dead stock** — no outward movement in N days; ties up capital.
- **Expiry watch** — batches expiring within 90 days.
- **Asset register** — full fixed-asset schedule with accumulated depreciation, the
  document external auditors ask for.
- **Custody report** — what each employee holds; feeds the **termination clearance**
  gate (an employee holding unreturned assets cannot be cleared, mirroring how
  `EmployeeLoan` already blocks clearance until paid).
- **Consumption by department** — issue volume per department per period, for budgeting.
- **Supplier performance** — spend, lead time, arrival condition.
- **Stocktake variance** — counted vs system, by session and by counter.

Exports follow the existing Excel/PDF plumbing used by payroll.

---

## 7. Deliberate boundaries

- **Library ILS is not touched.** Books stay in `books`/`book_copies` with their own
  Floor→Row→ShelfBox spatial model and their own circulation rules (fines, holds,
  campus checks). The store module is for everything that is *not* a catalogued
  library holding. Shared physical anchor: `Campus`.
- **No new spatial hierarchy for the library's benefit.** `inventory_locations` is a
  generic tree because a store cares about bins and offices, not shelves-of-books.
- **`Category` is already taken** by the library, as are `Loan`, `Transfer`,
  `Stocktake`. Hence the `Inventory*` prefix on every new model.
- **Procurement (PO → quotation → approval) is out of scope.** `inventory_batches`
  records a *purchase_order_number* so a future procurement module can link back
  without a schema change.
- **Depreciation posts nothing to the ledger.** The asset register computes and reports
  depreciation; no general-ledger integration exists to post it to, and inventing one
  is a separate decision.

---

## 8. Delivery plan

| Phase | Scope | Ships |
|---|---|---|
| **0 — Access** ✅ | `Store Keeper` role, 15 permissions, `StorePermission` enum + seeder, `StoreNavigation`, `/store` portal + dashboard, permission-gated route surface, President sidebar section, feature tests | *done* |
| **1 — Foundation** ✅ | Migrations for all 14 tables, 13 enums, 14 models + relations + factories, `InventoryCodeGenerator`, categories / suppliers / locations CRUD | *done — a catalog you can populate* |
| **2 — Catalog & receiving** | Items (photo, tracking mode, reorder level), supplier invoices/documents, GRN receiving, dashboard reorder alerts. | *done* — "what do we own, how much, where" |
| **3 — Issue & requisition** | Requisition maker-checker flow, issue vouchers, returns, inter-location transfers, employee self-service request page | day-to-day store operations |
| **4 — Assets** | Asset register, tag/QR generation, custody assign/return with handover slips, maintenance logs, depreciation schedule, disposal with approval | fixed-asset control |
| **5 — Control & insight** | Stocktake sessions with QR counting, variance posting, the nine reports, Excel/PDF export, termination-clearance hook | audit-ready |
| **6 — Scale** | Balance cache if measured necessary, Scout/Meilisearch indexing of items & assets, barcode label printing, dashboard trend charts | polish |

Each phase is independently shippable and leaves the module in a working state.

---

## 9. What Phase 1 shipped

Migrations (4 files, 14 tables): `..._create_inventory_reference_tables`,
`..._create_inventory_item_tables`, `..._create_inventory_asset_tables`,
`..._create_inventory_movement_tables`.

Enums (13): `InventoryTrackingMode`, `InventoryMovementType`, `InventoryUnitStatus`,
`InventoryCondition`, `InventoryLocationType`, `InventoryItemStatus`,
`InventoryRequestStatus`, `InventoryStocktakeStatus`, `InventoryDisposalMethod`,
`InventoryDisposalStatus`, `InventoryMaintenanceType`, `DepreciationMethod`,
`UnitOfMeasure`.

Models (14), all with relations, scopes and derived accessors. The load-bearing ones:

- `InventoryItem::onHand(?locationId)` — sums the signed ledger, optionally over a
  location subtree; `scopeNeedingReorder()` resolves the same sum in SQL so the
  dashboard doesn't load the catalog to find a handful of alerts.
- `InventoryStockMovement` — `updating` and `deleting` both throw. The ledger is
  append-only at the model level, so no controller, service or console command can
  quietly rewrite history (invariant 4).
- `InventoryUnit::bookValue()` / `accumulatedDepreciation()` — policy resolved
  unit → item → category.
- `InventoryStocktakeLine` — recomputes `variance` on every save, so a counted figure
  and its variance can never disagree.

`App\Support\Inventory\InventoryCodeGenerator` issues every identifier in §4. Sequences
are read off the data under a lock rather than a counter table (a counter that drifts
is worse than none), soft-deleted rows count toward the sequence because their unique
code survives deletion, and codes sort by length first so `IT-00009` doesn't outrank
`IT-00010`.

Live pages: **Categories**, **Suppliers**, **Store Locations** — full CRUD, read gated
by `view inventory`, writes by each entity's manage permission. Two protective
behaviours worth noting: a supplier with receipts on file and a location with stock
history are *deactivated*, never deleted, so the purchase and ledger history stay
readable.

Covered by `tests/Feature/InventoryFoundationTest.php` (24 tests).

## 9a. What Phase 2 shipped

`App\Services\Inventory\StockLedger` — the one place `inventory_stock_movements` rows
are created from application code. `receive()` posts a GRN: for a consumable item it
posts one inward movement; for an asset-tracked item it creates one `InventoryUnit` (with
its own generated asset tag) and one movement per unit received, so invariant 2 (an
asset-mode item never gets a bare quantity movement) holds from the first receipt onward.
The negative-stock guard (invariant 1) is written once here, under a locked read, so every
later phase's outward movement (issue, transfer, write-off, disposal) inherits it rather
than re-implementing it.

Live pages: **Items** (full CRUD, photo + document upload, category-driven tracking-mode
default) and **Receive Stock / GRN** (posts through `StockLedger`, branches automatically
on the selected item's tracking mode). The store dashboard's reorder-alert tile is wired
to `InventoryItem::needingReorder()` from Phase 1.

Covered by `tests/Feature/InventoryCatalogReceivingTest.php`.

## 10. Build & deploy note

`public/build` is committed (production cPanel has no Node). Every phase that touches
`resources/` must ship `npm run build` output in the same commit — see
`docs/deploy-to-cpanel.md`.
