# SITS Inventory & Asset Management (the "Store" module)
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

## 9b. What Phase 3 shipped

`App\Services\Inventory\StockLedger` extended with `issue()`, `returnStock()`, and `transfer()`:
- `issue()` creates `ISV-YYYY-XXXX` vouchers, validates line quantities (`quantity_issued ≤ quantity_approved ≤ quantity_requested`), marks `InventoryRequest` as `PartiallyFulfilled` or `Fulfilled`, transitions serialized asset units to `InUse`, and posts outward movements with negative-stock locking.
- `returnStock()` receives items or equipment back into store, updates location, clears active asset assignments, and posts inward `Return` movements.
- `transfer()` moves inventory atomically between locations via paired `TransferOut` and `TransferIn` movements while enforcing the source location's negative-stock guard.

Live pages: **Material Requisitions** (full maker-checker lifecycle, approval line trimming, issuance modal), **Issue Vouchers & Returns** (issue vouchers log, direct issue modal, return modal), and **Inter-Location Transfers** (dispatch & receipt history).

Covered by `tests/Feature/InventoryRequisitionIssueTest.php`.

## 10. Build & deploy note

`public/build` is committed (production cPanel has no Node). Every phase that touches
`resources/` must ship `npm run build` output in the same commit — see
`docs/deploy-to-cpanel.md`.
