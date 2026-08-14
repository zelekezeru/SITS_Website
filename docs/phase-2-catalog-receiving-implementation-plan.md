# Phase 2 — Catalog & Receiving: Implementation Plan

Target: ship the "Catalog & receiving" phase of the Store module exactly as scoped in
`docs/inventory-management-design.md` §8 — *"Items CRUD with image/document upload, GRN
receiving, the `StockLedger` write service enforcing the negative-stock guard, reorder
alerts on the dashboard."*

This plan is written to be executed literally, in order. Every step names the exact file,
gives the exact code (not a description of the code), and ends with a command you run to
verify that step before moving to the next one. Do not skip the verification commands —
if a step's verification fails, stop and fix it before starting the next step.

**Read this whole document before writing anything.** Section 0 is not optional context —
it is the set of existing conventions every new file below must match. Do not invent a
different pattern (a different form library, a different permission-check idiom, a
different file-upload approach) even if it seems reasonable in isolation — the codebase
already has one way to do each of these things, and this plan uses it throughout.

---

## 0. Ground rules

1. **PHP 8.2 syntax only** — no 8.3-only features (this is a hard project constraint).
2. **This repo tracks `public/build` in git** — production has no Node. Any change under
   `resources/` requires `npm run build` and the regenerated `public/build` committed in
   the same commit. Do this as the last step, once, not after every file.
3. **Do not create a migration.** All 14 Phase 1 tables already exist, including
   `inventory_items`, `inventory_batches`, `inventory_units`, `inventory_stock_movements`.
   This phase writes application code against the existing schema only.
4. **Do not add a new permission.** `manage inventory catalog` and `receive inventory`
   already exist (`App\Enums\StorePermission`) and are already granted to the Store
   Keeper role. If a step below seems to need a new permission, it doesn't — re-read the
   step.
5. **Follow existing files, don't improvise.** Before writing each new file, this plan
   tells you which existing file to open and copy the *pattern* from (not the content).
   The existing files are the specification for "how this codebase does X" — a subtly
   different but "equally valid" approach is a defect, not a stylistic choice.
6. **Run the verification command after every step.** Most steps end with a `php artisan`
   or `php -l` command. If it fails, the step is not done.
7. **Never edit `InventoryStockMovement` rows directly, anywhere.** The model throws on
   `update()`/`delete()` by design (append-only ledger). Every new movement is created
   through `StockLedger`, never `InventoryStockMovement::create()` from a controller.

### Files to read first (do this now)

Open and read these before writing anything — they are the templates this plan builds on:

| File | Why |
|---|---|
| `app/Http/Controllers/Store/ReferenceDataController.php` | The exact controller shape: `index()` returns Inertia with a `shell()` helper, validation in a private method, deactivate-not-delete pattern |
| `app/Http/Controllers/Store/DashboardController.php` | Where the reorder-alert data and the roadmap array are added |
| `routes/erp.php` lines 62–122 | Exactly how Store routes are registered, gated, and excluded from the generic module placeholder loop |
| `app/Models/InventoryItem.php`, `InventoryBatch.php`, `InventoryUnit.php`, `InventoryStockMovement.php` | Already-built relations and derived methods this phase calls — do not re-implement anything already here (`onHand()`, `needsReorder()`, `isSerialized()`, etc.) |
| `app/Support/Inventory/InventoryCodeGenerator.php` | `itemCode()`, `grnNumber()`, `assetTag()` already exist — call them, don't reimplement |
| `app/Support/DocumentUploader.php` and its call site `app/Http/Controllers/Admin/OrganizationArchiveController.php` | The exact file-upload pattern to reuse for item photos |
| `resources/js/Pages/Store/Suppliers.vue` | The exact Vue page shape: header, stat cards, search, table, modal form with `useForm`, `useConfirm` |
| `tests/Feature/InventoryFoundationTest.php` | Existing test conventions: `inventoryUser()` helper, seeders, `describe()` blocks |

---

## 1. `StockLedger` — the write service

**File (new):** `app/Services/Inventory/StockLedger.php`

This is the load-bearing piece of the whole phase — the only place
`inventory_stock_movements` rows get created from application code from now on. Later
phases (issue, transfer, adjustment, disposal) add thin methods here that validate their
own preconditions and then call the same private `postMovement()`, so the negative-stock
guard can never be bypassed by a future phase forgetting to re-check it.

Create the directory `app/Services/Inventory/` and this file inside it:

```php
<?php

namespace App\Services\Inventory;

use App\Enums\InventoryCondition;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryUnitStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Models\User;
use App\Support\Inventory\InventoryCodeGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place inventory_stock_movements is written from application code.
 *
 * Every post happens inside a transaction that locks the rows the negative-stock
 * guard reads, so two concurrent outward posts against the same item can't both
 * read a stale balance and both pass (invariant 1, docs/inventory-management-design.md
 * §5). InventoryMovementType::direction() is the only authority on the sign —
 * nothing here decides it independently.
 *
 * Phase 2 ships receive(). issue()/transfer()/adjust()/disposeOf() land with
 * Phases 3–5 as thin wrappers that validate their own preconditions and then call
 * the same postMovement() primitive, so the guard can't be bypassed later.
 */
class StockLedger
{
    /**
     * Record one goods-received event. Creates the InventoryBatch, posts the
     * inward ledger movement(s), and — for asset-tracked items — creates one
     * InventoryUnit per unit received, each with its own generated asset tag
     * (invariant 2: an asset-mode item never gets a bare quantity movement).
     *
     * @param  array<string, mixed>  $data  validated ReceivingController input
     */
    public function receive(array $data, User $registeredBy): InventoryBatch
    {
        return DB::transaction(function () use ($data, $registeredBy) {
            $item = $this->lockForUpdate(InventoryItem::query()->where('id', $data['item_id']))->firstOrFail();

            $quantity = (float) $data['quantity_received'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity_received' => 'Quantity received must be greater than zero.',
                ]);
            }

            if ($item->isSerialized() && $quantity !== floor($quantity)) {
                throw ValidationException::withMessages([
                    'quantity_received' => 'Serialized items must be received in whole units — one row per asset.',
                ]);
            }

            $unitCost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : null;
            $totalCost = isset($data['total_cost'])
                ? (float) $data['total_cost']
                : ($unitCost !== null ? round($unitCost * $quantity, 2) : null);

            $batch = InventoryBatch::create([
                'grn_number' => InventoryCodeGenerator::grnNumber(),
                'item_id' => $item->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'quantity_received' => $quantity,
                'unit_cost' => $unitCost,
                'currency' => $data['currency'] ?? 'ETB',
                'total_cost' => $totalCost,
                'purchase_date' => $data['purchase_date'],
                'production_date' => $data['production_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'warranty_until' => $data['warranty_until'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchase_order_number' => $data['purchase_order_number'] ?? null,
                'delivery_note_number' => $data['delivery_note_number'] ?? null,
                'condition_on_arrival' => $data['condition_on_arrival'] ?? InventoryCondition::New->value,
                'received_by_employee_id' => $data['received_by_employee_id'] ?? null,
                'registered_by' => $registeredBy->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($item->isSerialized()) {
                $this->receiveUnits($item, $batch, (int) $quantity, $data, $unitCost, $registeredBy);
            } else {
                $this->postMovement(
                    item: $item,
                    batch: $batch,
                    type: InventoryMovementType::Receipt,
                    quantity: $quantity,
                    fromLocationId: null,
                    toLocationId: $data['location_id'] ?? null,
                    unitCost: $unitCost,
                    performedBy: $registeredBy,
                    occurredAt: $batch->purchase_date,
                );
            }

            return $batch->fresh(['item', 'supplier', 'location', 'units']);
        });
    }

    /** One InventoryUnit + one ledger movement per unit received. */
    private function receiveUnits(InventoryItem $item, InventoryBatch $batch, int $count, array $data, ?float $unitCost, User $registeredBy): void
    {
        for ($i = 0; $i < $count; $i++) {
            $unit = InventoryUnit::create([
                'item_id' => $item->id,
                'batch_id' => $batch->id,
                'asset_tag' => InventoryCodeGenerator::assetTag($item),
                'status' => InventoryUnitStatus::InStore,
                'condition' => $data['condition_on_arrival'] ?? InventoryCondition::New->value,
                'current_location_id' => $data['location_id'] ?? null,
                'purchase_cost' => $unitCost,
                'depreciation_method' => $item->depreciation_method?->value,
                'useful_life_months' => $item->useful_life_months,
                'in_service_on' => $data['purchase_date'],
                'warranty_until' => $data['warranty_until'] ?? null,
                'created_by' => $registeredBy->id,
            ]);

            $this->postMovement(
                item: $item,
                batch: $batch,
                type: InventoryMovementType::Receipt,
                quantity: 1,
                fromLocationId: null,
                toLocationId: $data['location_id'] ?? null,
                unitCost: $unitCost,
                performedBy: $registeredBy,
                occurredAt: $batch->purchase_date,
                unit: $unit,
            );
        }
    }

    /**
     * Append one ledger row. Every movement — this phase's and every later
     * phase's — must go through here: it is the one place that (a) lets the
     * type decide the sign and (b) enforces the negative-stock guard.
     */
    private function postMovement(
        InventoryItem $item,
        ?InventoryBatch $batch,
        InventoryMovementType $type,
        float $quantity,
        ?int $fromLocationId,
        ?int $toLocationId,
        ?float $unitCost,
        User $performedBy,
        $occurredAt = null,
        ?InventoryUnit $unit = null,
        ?string $reference = null,
        ?string $reason = null,
    ): InventoryStockMovement {
        $signed = $type->isSignedByCaller() ? $quantity : abs($quantity) * $type->direction();

        if ($type->isOutward()) {
            $this->assertSufficientStock($item, $fromLocationId, abs($signed));
        }

        return InventoryStockMovement::create([
            'item_id' => $item->id,
            'unit_id' => $unit?->id,
            'batch_id' => $batch?->id,
            'type' => $type,
            'quantity' => $signed,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'reference' => $reference,
            'unit_cost' => $unitCost,
            'occurred_at' => $occurredAt ?? now(),
            'performed_by' => $performedBy->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Invariant 1. Locks every existing movement row for this item (and, when
     * scoped, this location's subtree) before summing, so a concurrent post
     * can't read the same stale balance and also pass.
     */
    private function assertSufficientStock(InventoryItem $item, ?int $locationId, float $quantityOut): void
    {
        $query = $this->lockForUpdate(InventoryStockMovement::query()->where('item_id', $item->id));

        if ($locationId !== null) {
            $ids = InventoryLocation::find($locationId)?->descendantIds() ?? [$locationId];
            $query->where(fn (Builder $q) => $q->whereIn('to_location_id', $ids)->orWhereIn('from_location_id', $ids));
        }

        $onHand = (float) $query->sum('quantity');

        if (round($onHand - $quantityOut, 3) < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$onHand} on hand — cannot remove {$quantityOut}.",
            ]);
        }
    }

    /**
     * `lockForUpdate` is a no-op on SQLite and errors outside a transaction on
     * some drivers; guard it the same way InventoryCodeGenerator does so
     * behaviour is identical across MySQL (production) and SQLite (should any
     * test environment use it).
     */
    private function lockForUpdate(Builder $query): Builder
    {
        return DB::connection()->getDriverName() === 'sqlite' ? $query : $query->lockForUpdate();
    }
}
```

**Verify:**
```bash
php -l app/Services/Inventory/StockLedger.php
```
Expected: `No syntax errors detected`.

---

## 2. Catalog controller — Items CRUD

**File (new):** `app/Http/Controllers/Store/CatalogController.php`

Follows `ReferenceDataController.php`'s exact shape: a public `index`-style method per
page, private `validate*` and `shell` helpers. The item's own `code` is **never** accepted
from the client — it is always `InventoryCodeGenerator::itemCode($category)`, exactly like
`storeSupplier()` never accepts a client-supplied `code`.

```php
<?php

namespace App\Http\Controllers\Store;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryItemStatus;
use App\Enums\InventoryTrackingMode;
use App\Enums\UnitOfMeasure;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Support\DocumentUploader;
use App\Support\Inventory\InventoryCodeGenerator;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The item catalog — Phase 2. Read is gated by `view inventory`; writes by
 * `manage inventory catalog`. See docs/inventory-management-design.md §2, §8.
 */
class CatalogController extends Controller
{
    public function items(Request $request): Response
    {
        $items = InventoryItem::with(['category:id,name_en', 'primaryImage', 'documents'])
            ->withCount(['batches', 'units'])
            ->orderBy('name_en')
            ->get()
            ->map(fn (InventoryItem $i) => $this->present($i));

        return Inertia::render('Store/Items', [
            ...$this->shell($request, 'store.items'),
            'items' => $items,
            'categories' => InventoryCategory::where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'tracking_mode', 'default_depreciation_method', 'default_useful_life_months']),
            'trackingModes' => self::enumOptions(InventoryTrackingMode::cases()),
            'unitsOfMeasure' => self::enumOptions(UnitOfMeasure::cases()),
            'statuses' => self::enumOptions(InventoryItemStatus::cases()),
            'depreciationMethods' => self::enumOptions(DepreciationMethod::cases()),
            'can' => ['manage' => (bool) $request->user()?->can('manage inventory catalog')],
        ]);
    }

    public function storeItem(Request $request)
    {
        $data = $this->validateItem($request);
        $category = InventoryCategory::findOrFail($data['category_id']);
        unset($data['image']);

        $item = InventoryItem::create([
            ...$data,
            'code' => InventoryCodeGenerator::itemCode($category),
            'created_by' => $request->user()->id,
        ]);

        $this->attachImageIfPresent($request, $item);

        return back()->with('success', "Item created as {$item->code}.");
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $data = $this->validateItem($request, $item);
        unset($data['image']);

        $item->update($data);

        $this->attachImageIfPresent($request, $item);

        return back()->with('success', 'Item updated.');
    }

    public function destroyItem(InventoryItem $item)
    {
        // A transaction history (a receipt, a unit, a movement) must stay
        // readable, so an item that has ever moved is archived, not deleted —
        // the same protective pattern as categories, suppliers and locations.
        if ($item->batches()->exists() || $item->units()->exists() || $item->movements()->exists()) {
            $item->update(['status' => InventoryItemStatus::Archived]);

            return back()->with('success', 'This item has transaction history, so it was archived rather than deleted.');
        }

        $item->delete();

        return back()->with('success', 'Item deleted.');
    }

    public function storeItemDocument(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['image', 'invoice', 'warranty', 'manual'])],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $document = DocumentUploader::store(
            title: $data['title'],
            documentableType: InventoryItem::class,
            documentableId: $item->id,
            file: $request->file('file'),
            filePath: null,
            uploadedBy: $request->user()->id,
            category: $data['category'],
        );

        if ($data['category'] === 'image' && $item->primary_image_id === null) {
            $item->update(['primary_image_id' => $document->id]);
        }

        return back()->with('success', 'Document attached.');
    }

    public function destroyItemDocument(InventoryItem $item, Document $document)
    {
        abort_unless(
            $document->documentable_type === InventoryItem::class && $document->documentable_id === $item->id,
            404
        );

        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    // ==========================================================================

    private function attachImageIfPresent(Request $request, InventoryItem $item): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $document = DocumentUploader::store(
            title: $item->name_en.' — photo',
            documentableType: InventoryItem::class,
            documentableId: $item->id,
            file: $request->file('image'),
            filePath: null,
            uploadedBy: $request->user()->id,
            category: 'image',
        );

        $item->update(['primary_image_id' => $document->id]);
    }

    /** @return array<string, mixed> */
    private function validateItem(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:inventory_categories,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tracking_mode' => ['required', Rule::enum(InventoryTrackingMode::class)],
            'unit_of_measure' => ['required', Rule::enum(UnitOfMeasure::class)],
            'status' => ['required', Rule::enum(InventoryItemStatus::class)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'standard_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tracks_expiry' => ['boolean'],
            'depreciation_method' => ['nullable', Rule::enum(DepreciationMethod::class)],
            'useful_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    /** @return array<string, mixed> */
    private function present(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'code' => $item->code,
            'category_id' => $item->category_id,
            'category' => $item->category?->name_en,
            'name_en' => $item->name_en,
            'name_am' => $item->name_am,
            'description' => $item->description,
            'tracking_mode' => $item->tracking_mode->value,
            'tracking_mode_label' => $item->tracking_mode->label(),
            'unit_of_measure' => $item->unit_of_measure->value,
            'unit_of_measure_label' => $item->unit_of_measure->label(),
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'status_tone' => $item->status->tone(),
            'brand' => $item->brand,
            'model' => $item->model,
            'specification' => $item->specification,
            'reorder_level' => (float) $item->reorder_level,
            'reorder_quantity' => $item->reorder_quantity !== null ? (float) $item->reorder_quantity : null,
            'standard_unit_cost' => $item->standard_unit_cost !== null ? (float) $item->standard_unit_cost : null,
            'tracks_expiry' => $item->tracks_expiry,
            'depreciation_method' => $item->depreciation_method?->value,
            'useful_life_months' => $item->useful_life_months,
            'notes' => $item->notes,
            'image_path' => $item->primaryImage?->path,
            // Deliberate N+1 (one onHand() query per row): catalog sizes here are in
            // the hundreds, not thousands. A cached balance is explicitly deferred to
            // Phase 6 in docs/inventory-management-design.md §1 — don't add one here.
            'on_hand' => $item->onHand(),
            'needs_reorder' => $item->needsReorder(),
            'batches_count' => $item->batches_count,
            'units_count' => $item->units_count,
            'documents' => $item->documents->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category' => $d->category,
                'path' => $d->path,
            ])->values(),
        ];
    }

    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private static function enumOptions(array $cases): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : $case->name,
        ], $cases);
    }
}
```

**Verify:**
```bash
php -l app/Http/Controllers/Store/CatalogController.php
```

---

## 3. Receiving controller — GRN

**File (new):** `app/Http/Controllers/Store/ReceivingController.php`

This controller does **no ledger math itself** — it validates the request and hands the
validated array to `StockLedger::receive()`. That boundary (controller validates and
translates HTTP → service enforces business rules) is deliberate; do not move any of the
`if` checks from `StockLedger` into this controller.

```php
<?php

namespace App\Http\Controllers\Store;

use App\Enums\InventoryCondition;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventorySupplier;
use App\Services\Inventory\StockLedger;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Goods-received notes (GRN). Read is gated by `view inventory`; posting a
 * receipt by `receive inventory`. All writes go through StockLedger.
 */
class ReceivingController extends Controller
{
    public function __construct(private StockLedger $ledger) {}

    public function index(Request $request): Response
    {
        $batches = InventoryBatch::with(['item:id,name_en,code,tracking_mode', 'supplier:id,name', 'location:id,name', 'registeredBy:id,name'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (InventoryBatch $b) => [
                'id' => $b->id,
                'grn_number' => $b->grn_number,
                'item' => $b->item?->name_en,
                'item_code' => $b->item?->code,
                'tracking_mode' => $b->item?->tracking_mode->value,
                'supplier' => $b->supplier?->name,
                'location' => $b->location?->name,
                'quantity_received' => (float) $b->quantity_received,
                'unit_cost' => $b->unit_cost !== null ? (float) $b->unit_cost : null,
                'total_cost' => $b->total_cost !== null ? (float) $b->total_cost : null,
                'purchase_date' => $b->purchase_date?->toDateString(),
                'expiry_date' => $b->expiry_date?->toDateString(),
                'registered_by' => $b->registeredBy?->name,
            ]);

        return Inertia::render('Store/Receipts', [
            ...$this->shell($request, 'store.receipts'),
            'batches' => $batches,
            'items' => InventoryItem::active()
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'code', 'tracking_mode', 'unit_of_measure', 'standard_unit_cost', 'tracks_expiry']),
            'suppliers' => InventorySupplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'locations' => InventoryLocation::storable()->active()->orderBy('name')->get(['id', 'name', 'parent_id']),
            'employees' => Employee::where('is_active', true)->orderBy('full_name_en')->get(['id', 'full_name_en', 'staff_no']),
            'conditions' => collect(InventoryCondition::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->values(),
            'can' => ['receive' => (bool) $request->user()?->can('receive inventory')],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'supplier_id' => ['nullable', 'exists:inventory_suppliers,id'],
            'location_id' => ['nullable', 'exists:inventory_locations,id'],
            'quantity_received' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'purchase_date' => ['required', 'date'],
            'production_date' => ['nullable', 'date', 'before_or_equal:purchase_date'],
            'expiry_date' => ['nullable', 'date', 'after:purchase_date'],
            'warranty_until' => ['nullable', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'purchase_order_number' => ['nullable', 'string', 'max:255'],
            'delivery_note_number' => ['nullable', 'string', 'max:255'],
            'condition_on_arrival' => ['nullable', Rule::enum(InventoryCondition::class)],
            'received_by_employee_id' => ['nullable', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $batch = $this->ledger->receive($data, $request->user());

        return back()->with('success', "Receipt {$batch->grn_number} recorded.");
    }

    private function shell(Request $request, string $routeName): array
    {
        return [
            'module' => StoreNavigation::module($routeName),
            'nav' => PortalContext::for($request->user())['nav'],
        ];
    }
}
```

**Verify:**
```bash
php -l app/Http/Controllers/Store/ReceivingController.php
```

---

## 4. Routes

**File (edit):** `routes/erp.php`

### 4a. Add two `use` imports

Find this block (around line 33–35):
```php
use App\Http\Controllers\Store\DashboardController as StoreDashboardController;
use App\Http\Controllers\Store\ModuleController as StoreModuleController;
use App\Http\Controllers\Store\ReferenceDataController as StoreReferenceController;
```
Add immediately after it:
```php
use App\Http\Controllers\Store\CatalogController as StoreCatalogController;
use App\Http\Controllers\Store\ReceivingController as StoreReceivingController;
```

### 4b. Register the routes

Find this block (around line 96–106):
```php
    Route::middleware('can:manage inventory suppliers')->group(function () {
        Route::post('/store/suppliers', [StoreReferenceController::class, 'storeSupplier'])->name('store.suppliers.store');
        Route::put('/store/suppliers/{supplier}', [StoreReferenceController::class, 'updateSupplier'])->name('store.suppliers.update');
        Route::delete('/store/suppliers/{supplier}', [StoreReferenceController::class, 'destroySupplier'])->name('store.suppliers.destroy');
    });

    Route::middleware('can:manage inventory locations')->group(function () {
```
Insert a new block **between** the suppliers block and the locations block:
```php
    Route::middleware('can:manage inventory suppliers')->group(function () {
        Route::post('/store/suppliers', [StoreReferenceController::class, 'storeSupplier'])->name('store.suppliers.store');
        Route::put('/store/suppliers/{supplier}', [StoreReferenceController::class, 'updateSupplier'])->name('store.suppliers.update');
        Route::delete('/store/suppliers/{supplier}', [StoreReferenceController::class, 'destroySupplier'])->name('store.suppliers.destroy');
    });

    // Items — the catalog. Reading needs only `view inventory`; every write needs
    // `manage inventory catalog`.
    Route::get('/store/items', [StoreCatalogController::class, 'items'])
        ->middleware('can:view inventory')->name('store.items');

    Route::middleware('can:manage inventory catalog')->group(function () {
        Route::post('/store/items', [StoreCatalogController::class, 'storeItem'])->name('store.items.store');
        Route::put('/store/items/{item}', [StoreCatalogController::class, 'updateItem'])->name('store.items.update');
        Route::delete('/store/items/{item}', [StoreCatalogController::class, 'destroyItem'])->name('store.items.destroy');
        Route::post('/store/items/{item}/documents', [StoreCatalogController::class, 'storeItemDocument'])->name('store.items.documents.store');
        Route::delete('/store/items/{item}/documents/{document}', [StoreCatalogController::class, 'destroyItemDocument'])->name('store.items.documents.destroy');
    });

    // Receiving (GRN). Reading needs `view inventory`; posting a receipt needs the
    // narrower `receive inventory` — a Store Keeper can see the catalog without
    // being able to bring stock in, matching the permission matrix in
    // docs/inventory-management-design.md §3.
    Route::get('/store/receipts', [StoreReceivingController::class, 'index'])
        ->middleware('can:view inventory')->name('store.receipts');

    Route::middleware('can:receive inventory')->group(function () {
        Route::post('/store/receipts', [StoreReceivingController::class, 'store'])->name('store.receipts.store');
    });

    Route::middleware('can:manage inventory locations')->group(function () {
```

### 4c. Exclude the two new pages from the generic placeholder loop

Find this line (around line 112):
```php
    $storePagesBuilt = ['store.categories', 'store.suppliers', 'store.locations'];
```
Change it to:
```php
    $storePagesBuilt = ['store.categories', 'store.suppliers', 'store.locations', 'store.items', 'store.receipts'];
```

**Verify:**
```bash
php artisan route:list --path=store
```
Expected: rows for `GET|HEAD store/items`, `POST store/items`, `PUT store/items/{item}`,
`DELETE store/items/{item}`, `POST store/items/{item}/documents`,
`DELETE store/items/{item}/documents/{document}`, `GET|HEAD store/receipts`,
`POST store/receipts` — all pointing at `Store\CatalogController` / `Store\ReceivingController`,
not `Store\ModuleController`.

---

## 5. Dashboard — reorder alerts + roadmap status

**File (edit):** `app/Http/Controllers/Store/DashboardController.php`

### 5a. Add the import

At the top, add:
```php
use App\Models\InventoryItem;
```

### 5b. Add reorder-alert props inside `index()`

Find the `return Inertia::render('Store/Dashboard', [` block and add two new keys
(anywhere inside the array, e.g. right after `'can' => $can,`):
```php
            'reorderAlerts' => InventoryItem::needingReorder()
                ->with('category:id,name_en')
                ->orderBy('name_en')
                ->limit(8)
                ->get()
                ->map(fn (InventoryItem $i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'name_en' => $i->name_en,
                    'category' => $i->category?->name_en,
                    'on_hand' => $i->onHand(),
                    'reorder_level' => (float) $i->reorder_level,
                ]),
            'reorderAlertsTotal' => InventoryItem::needingReorder()->count(),
```

### 5c. Flip the roadmap status

Find the `ROADMAP` constant and change Phase 2's `status` from `'next'` to `'done'`, and
Phase 3's from `'planned'` to `'next'`:
```php
        ['phase' => 'Phase 2', 'title' => 'Catalog & receiving', 'status' => 'done',
            'detail' => 'Items with photos, goods-received notes, the movement ledger and reorder alerts.'],
        ['phase' => 'Phase 3', 'title' => 'Issue & requisition', 'status' => 'next',
            'detail' => 'Requisition approval flow, issue vouchers, returns and inter-location transfers.'],
```

**Verify:**
```bash
php -l app/Http/Controllers/Store/DashboardController.php
```

---

## 6. Frontend — `Store/Items.vue`

**File (new):** `resources/js/Pages/Store/Items.vue`

Model this file's visual language (header gradient, stat cards, search bar, table,
slide-in modal) on `resources/js/Pages/Store/Suppliers.vue` — same amber accent colours,
same `AdminLayout`, same `useForm` + `useConfirm` pattern. The structure below is the
functional part (props, fields, conditional logic); keep the class names consistent with
Suppliers.vue rather than inventing new spacing/colour choices.

```vue
<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  items: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  trackingModes: { type: Array, default: () => [] },
  unitsOfMeasure: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  depreciationMethods: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ---- Filters ----------------------------------------------------------------
const search = ref('');
const modeFilter = ref('');
const statusFilter = ref('active');

const filtered = computed(() => {
  const q = search.value.toLowerCase();
  return props.items.filter((i) => {
    if (modeFilter.value && i.tracking_mode !== modeFilter.value) return false;
    if (statusFilter.value && i.status !== statusFilter.value) return false;
    if (!q) return true;
    return `${i.name_en} ${i.code} ${i.brand ?? ''} ${i.model ?? ''}`.toLowerCase().includes(q);
  });
});

const summary = computed(() => ({
  total: props.items.length,
  assets: props.items.filter((i) => i.tracking_mode === 'asset').length,
  consumables: props.items.filter((i) => i.tracking_mode === 'consumable').length,
  reorder: props.items.filter((i) => i.needs_reorder).length,
}));

// ---- Create / edit modal -----------------------------------------------------
const editing = ref(null);
const open = ref(false);
const imageInput = ref(null);

const blankForm = () => ({
  category_id: '', name_en: '', name_am: '', description: '',
  tracking_mode: 'consumable', unit_of_measure: 'piece', status: 'active',
  brand: '', model: '', specification: '',
  reorder_level: 0, reorder_quantity: '', standard_unit_cost: '',
  tracks_expiry: false, depreciation_method: '', useful_life_months: '',
  notes: '', image: null,
});

const form = useForm(blankForm());

const openCreate = () => {
  editing.value = null;
  form.defaults(blankForm());
  form.reset();
  form.clearErrors();
  if (imageInput.value) imageInput.value.value = '';
  open.value = true;
};

const openEdit = (item) => {
  editing.value = item;
  form.clearErrors();
  form.category_id = item.category_id;
  form.name_en = item.name_en;
  form.name_am = item.name_am ?? '';
  form.description = item.description ?? '';
  form.tracking_mode = item.tracking_mode;
  form.unit_of_measure = item.unit_of_measure;
  form.status = item.status;
  form.brand = item.brand ?? '';
  form.model = item.model ?? '';
  form.specification = item.specification ?? '';
  form.reorder_level = item.reorder_level ?? 0;
  form.reorder_quantity = item.reorder_quantity ?? '';
  form.standard_unit_cost = item.standard_unit_cost ?? '';
  form.tracks_expiry = item.tracks_expiry;
  form.depreciation_method = item.depreciation_method ?? '';
  form.useful_life_months = item.useful_life_months ?? '';
  form.notes = item.notes ?? '';
  form.image = null;
  if (imageInput.value) imageInput.value.value = '';
  open.value = true;
};

// A category's tracking_mode is the DEFAULT for a new item — pick it up once,
// but don't fight the user if they then change it deliberately.
const onCategoryChange = () => {
  if (editing.value) return; // don't re-default on edit
  const cat = props.categories.find((c) => c.id === Number(form.category_id));
  if (cat) {
    form.tracking_mode = cat.tracking_mode;
    if (cat.default_depreciation_method) form.depreciation_method = cat.default_depreciation_method;
    if (cat.default_useful_life_months) form.useful_life_months = cat.default_useful_life_months;
  }
};

const onImagePicked = (e) => { form.image = e.target.files[0] ?? null; };

const submit = () => {
  const options = { preserveScroll: true, onSuccess: () => { open.value = false; } };

  // File uploads on an update must go through POST with a spoofed _method —
  // Laravel reads multipart bodies correctly on POST; a real PUT with a
  // multipart body is not guaranteed to parse. This mirrors the @method()
  // spoofing already used elsewhere in this app (see oauth/authorize.blade.php).
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' }))
        .post(`/store/items/${editing.value.id}`, options);
  } else {
    form.post('/store/items', options);
  }
};

const remove = async (item) => {
  const ok = await confirm({
    title: 'Delete Item',
    message: `Delete "${item.name_en}"? Items with a receipt, unit or movement on file are archived instead, so history stays intact.`,
  });
  if (ok) router.delete(`/store/items/${item.id}`, { preserveScroll: true });
};

// ---- Attachments (only inside an existing item) ------------------------------
const docForm = useForm({ title: '', category: 'invoice', file: null });
const docFileInput = ref(null);

const onDocPicked = (e) => { docForm.file = e.target.files[0] ?? null; };

const submitDoc = () => {
  if (!editing.value) return;
  docForm.post(`/store/items/${editing.value.id}/documents`, {
    preserveScroll: true,
    onSuccess: () => { docForm.reset(); if (docFileInput.value) docFileInput.value.value = ''; },
  });
};

const removeDoc = (doc) => {
  if (!editing.value) return;
  router.delete(`/store/items/${editing.value.id}/documents/${doc.id}`, { preserveScroll: true });
};
</script>

<template>
  <Head title="Items — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="Package" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Catalog</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Items</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Every material and asset the Seminary owns, with photos, specification and reorder policy.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Item
        </button>
      </div>
    </section>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Items</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ summary.total }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Consumables</p>
        <p class="text-2xl font-extrabold text-blue-400 mt-1">{{ summary.consumables }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Fixed Assets</p>
        <p class="text-2xl font-extrabold text-violet-400 mt-1">{{ summary.assets }}</p>
      </div>
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Needs Reorder</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ summary.reorder }}</p>
      </div>
    </div>

    <div class="flex items-center gap-4 flex-wrap">
      <div class="relative w-full max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
        <input v-model="search" type="text" placeholder="Search name, code, brand…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500" />
      </div>
      <select v-model="modeFilter" class="bg-slate-900/40 border border-slate-900 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-amber-500">
        <option value="">All modes</option>
        <option v-for="m in trackingModes" :key="m.value" :value="m.value">{{ m.label }}</option>
      </select>
      <select v-model="statusFilter" class="bg-slate-900/40 border border-slate-900 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-amber-500">
        <option value="">All statuses</option>
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1040px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Item</th>
            <th class="p-3">Category</th>
            <th class="p-3">Mode</th>
            <th class="p-3 text-right">On hand</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="i in filtered" :key="i.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3">
              <div class="flex items-center gap-3">
                <img v-if="i.image_path" :src="'/' + i.image_path" class="w-9 h-9 rounded-lg object-cover border border-slate-800" />
                <span v-else class="w-9 h-9 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600"><Icon name="Package" :size="16" /></span>
                <div class="min-w-0">
                  <p class="font-semibold text-slate-200">{{ i.name_en }}</p>
                  <p class="font-mono text-[11px] text-amber-400/80">{{ i.code }}</p>
                </div>
              </div>
            </td>
            <td class="p-3 text-xs text-slate-400">{{ i.category || '—' }}</td>
            <td class="p-3">
              <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-lg" :class="i.tracking_mode === 'asset' ? 'bg-violet-500/10 text-violet-300 border border-violet-500/20' : 'bg-blue-500/10 text-blue-300 border border-blue-500/20'">
                {{ i.tracking_mode_label }}
              </span>
            </td>
            <td class="p-3 text-right font-mono" :class="i.needs_reorder ? 'text-amber-400 font-bold' : 'text-slate-300'">
              {{ i.on_hand }} <span class="text-slate-600">{{ i.unit_of_measure }}</span>
            </td>
            <td class="p-3">
              <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-lg" :class="`bg-${i.status_tone}-500/10 text-${i.status_tone}-300 border border-${i.status_tone}-500/20`">
                {{ i.status_label }}
              </span>
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="can.manage">
                <button @click="openEdit(i)" class="text-[11px] font-bold px-2.5 py-1.5 text-amber-400 hover:text-amber-300">Edit</button>
                <button @click="remove(i)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-500 hover:text-rose-400">Delete</button>
              </template>
              <span v-else class="text-[11px] text-slate-600">View only</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="6" class="p-8 text-center text-slate-500 italic">
              {{ search ? 'No items match that search.' : 'No items yet — add the first one.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-1">{{ editing ? 'Edit Item' : 'New Item' }}</h3>
        <p v-if="editing" class="font-mono text-xs text-amber-400/80 mb-6">{{ editing.code }}</p>
        <p v-else class="text-xs text-slate-500 mb-6">A SKU is generated automatically once saved.</p>

        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Category</label>
              <select v-model="form.category_id" @change="onCategoryChange" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="" disabled>Select a category…</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_en }}</option>
              </select>
              <p v-if="form.errors.category_id" class="text-xs text-rose-400 mt-1">{{ form.errors.category_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name (English)</label>
              <input v-model="form.name_en" type="text" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.name_en" class="text-xs text-rose-400 mt-1">{{ form.errors.name_en }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name (Amharic)</label>
              <input v-model="form.name_am" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tracking Mode</label>
              <select v-model="form.tracking_mode" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="m in trackingModes" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Unit of Measure</label>
              <select v-model="form.unit_of_measure" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="u in unitsOfMeasure" :key="u.value" :value="u.value">{{ u.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Status</label>
              <select v-model="form.status" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Brand</label>
              <input v-model="form.brand" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Model</label>
              <input v-model="form.model" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Specification</label>
              <textarea v-model="form.specification" rows="2" placeholder="Size, colour, rating, capacity…" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reorder Level</label>
              <input v-model="form.reorder_level" type="number" step="0.001" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reorder Quantity</label>
              <input v-model="form.reorder_quantity" type="number" step="0.001" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Standard Unit Cost (planning)</label>
              <input v-model="form.standard_unit_cost" type="number" step="0.01" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <label class="flex items-center gap-3 cursor-pointer pt-7">
              <input v-model="form.tracks_expiry" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
              <span class="text-sm text-slate-300">Batches of this item carry an expiry date</span>
            </label>

            <!-- Depreciation — only meaningful for fixed assets -->
            <template v-if="form.tracking_mode === 'asset'">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Depreciation Method</label>
                <select v-model="form.depreciation_method" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                  <option value="">Inherit from category</option>
                  <option v-for="d in depreciationMethods" :key="d.value" :value="d.value">{{ d.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Useful Life (months)</label>
                <input v-model="form.useful_life_months" type="number" min="1" max="1200" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              </div>
            </template>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Photo</label>
              <input ref="imageInput" @change="onImagePicked" type="file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-500/10 file:text-amber-400 hover:file:bg-amber-500/20" />
              <img v-if="editing?.image_path" :src="'/' + editing.image_path" class="mt-3 w-20 h-20 rounded-xl object-cover border border-slate-800" />
              <p v-if="form.errors.image" class="text-xs text-rose-400 mt-1">{{ form.errors.image }}</p>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</label>
              <textarea v-model="form.notes" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Add item') }}
            </button>
          </div>
        </form>

        <!-- Attachments — only once the item exists -->
        <div v-if="editing" class="mt-8 pt-6 border-t border-slate-900">
          <h4 class="text-sm font-bold text-slate-300 mb-4">Attachments</h4>
          <ul class="space-y-2 mb-4">
            <li v-for="d in editing.documents" :key="d.id" class="flex items-center justify-between bg-slate-900/30 rounded-xl px-4 py-2.5">
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 shrink-0">{{ d.category }}</span>
                <span class="text-xs text-slate-300 truncate">{{ d.title }}</span>
              </div>
              <button @click="removeDoc(d)" class="text-[11px] font-bold text-slate-500 hover:text-rose-400 shrink-0 ml-3">Remove</button>
            </li>
            <li v-if="!editing.documents?.length" class="text-xs text-slate-600 italic">No attachments yet.</li>
          </ul>
          <form @submit.prevent="submitDoc" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[160px]">
              <input v-model="docForm.title" type="text" placeholder="Title (e.g. Purchase invoice)" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2 text-slate-100 text-xs focus:outline-none focus:border-amber-500/50" />
            </div>
            <select v-model="docForm.category" class="bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2 text-slate-100 text-xs focus:outline-none focus:border-amber-500/50">
              <option value="invoice">Invoice</option>
              <option value="warranty">Warranty</option>
              <option value="manual">Manual</option>
              <option value="image">Photo</option>
            </select>
            <input ref="docFileInput" @change="onDocPicked" type="file" class="text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-300" />
            <button type="submit" :disabled="docForm.processing || !docForm.file" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 disabled:opacity-40">Attach</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
```

**Verify:** none possible yet in isolation (Inertia pages aren't linted standalone) —
this is verified in step 9 once the route exists.

---

## 7. Frontend — `Store/Receipts.vue`

**File (new):** `resources/js/Pages/Store/Receipts.vue`

```vue
<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  batches: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
  locations: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  conditions: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const money = (n) => n === null || n === undefined ? '—' : Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const today = () => new Date().toISOString().slice(0, 10);

const open = ref(false);

const form = useForm({
  item_id: '', supplier_id: '', location_id: '',
  quantity_received: '', unit_cost: '', total_cost: '', currency: 'ETB',
  purchase_date: today(), production_date: '', expiry_date: '', warranty_until: '',
  invoice_number: '', purchase_order_number: '', delivery_note_number: '',
  condition_on_arrival: 'new', received_by_employee_id: '', notes: '',
});

const selectedItem = computed(() => props.items.find((i) => i.id === Number(form.item_id)) || null);
const isAsset = computed(() => selectedItem.value?.tracking_mode === 'asset');

const openCreate = () => {
  form.reset();
  form.clearErrors();
  form.purchase_date = today();
  form.condition_on_arrival = 'new';
  form.currency = 'ETB';
  open.value = true;
};

const submit = () => {
  form.post('/store/receipts', {
    preserveScroll: true,
    onSuccess: () => { open.value = false; },
  });
};
</script>

<template>
  <Head title="Receive Stock (GRN) — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="PackagePlus" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Stock</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Receive Stock (GRN)</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Every goods-received note: supplier, quantity, unit cost, and who physically took delivery.
              Serialized items get one asset tag per unit received automatically.
            </p>
          </div>
        </div>
        <button v-if="can.receive" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + Record Receipt
        </button>
      </div>
    </section>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1080px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">GRN</th>
            <th class="p-3">Item</th>
            <th class="p-3">Supplier</th>
            <th class="p-3">Location</th>
            <th class="p-3 text-right">Qty</th>
            <th class="p-3 text-right">Unit Cost</th>
            <th class="p-3 text-right">Total</th>
            <th class="p-3">Purchased</th>
            <th class="p-3">Registered by</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="b in batches" :key="b.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3 font-mono text-xs text-amber-400/90">{{ b.grn_number }}</td>
            <td class="p-3">
              <p class="font-semibold text-slate-200">{{ b.item }}</p>
              <p class="font-mono text-[11px] text-slate-500">{{ b.item_code }}</p>
            </td>
            <td class="p-3 text-xs text-slate-400">{{ b.supplier || '—' }}</td>
            <td class="p-3 text-xs text-slate-400">{{ b.location || '—' }}</td>
            <td class="p-3 text-right font-mono text-slate-300">{{ b.quantity_received }}</td>
            <td class="p-3 text-right font-mono text-slate-400">{{ money(b.unit_cost) }}</td>
            <td class="p-3 text-right font-mono text-slate-200">{{ money(b.total_cost) }}</td>
            <td class="p-3 text-xs text-slate-400">{{ b.purchase_date }}</td>
            <td class="p-3 text-xs text-slate-500">{{ b.registered_by || '—' }}</td>
          </tr>
          <tr v-if="!batches.length">
            <td colspan="9" class="p-8 text-center text-slate-500 italic">No receipts recorded yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">Record a Receipt</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Item</label>
              <select v-model="form.item_id" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="" disabled>Select an item…</option>
                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name_en }} ({{ i.code }})</option>
              </select>
              <p v-if="form.errors.item_id" class="text-xs text-rose-400 mt-1">{{ form.errors.item_id }}</p>
              <p v-if="isAsset" class="text-xs text-violet-400 mt-1.5">
                This is a fixed asset — quantity must be a whole number and one asset tag will be
                generated per unit received.
              </p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Quantity Received</label>
              <input v-model="form.quantity_received" type="number" :step="isAsset ? 1 : 0.001" min="0" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.quantity_received" class="text-xs text-rose-400 mt-1">{{ form.errors.quantity_received }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Unit Cost</label>
              <input v-model="form.unit_cost" type="number" step="0.01" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Total Cost (optional override)</label>
              <input v-model="form.total_cost" type="number" step="0.01" min="0" placeholder="Defaults to unit cost × quantity" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p class="text-[11px] text-slate-600 mt-1">Set this only if the invoice carries rounding, freight or fees.</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Currency</label>
              <input v-model="form.currency" type="text" maxlength="3" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm uppercase focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Supplier</label>
              <select v-model="form.supplier_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">—</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Location</label>
              <select v-model="form.location_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">—</option>
                <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Purchase Date</label>
              <input v-model="form.purchase_date" type="date" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.purchase_date" class="text-xs text-rose-400 mt-1">{{ form.errors.purchase_date }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Production Date</label>
              <input v-model="form.production_date" type="date" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div v-if="selectedItem?.tracks_expiry">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Expiry Date</label>
              <input v-model="form.expiry_date" type="date" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.expiry_date" class="text-xs text-rose-400 mt-1">{{ form.errors.expiry_date }}</p>
            </div>
            <div v-if="isAsset">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Warranty Until</label>
              <input v-model="form.warranty_until" type="date" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Condition on Arrival</label>
              <select v-model="form.condition_on_arrival" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Received By</label>
              <select v-model="form.received_by_employee_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">—</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Invoice Number</label>
              <input v-model="form.invoice_number" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Purchase Order Number</label>
              <input v-model="form.purchase_order_number" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Delivery Note Number</label>
              <input v-model="form.delivery_note_number" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</label>
              <textarea v-model="form.notes" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Recording…' : 'Record receipt' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
```

---

## 8. Dashboard — reorder alert tile

**File (edit):** `resources/js/Pages/Store/Dashboard.vue`

### 8a. Add props

In `defineProps({...})`, add:
```js
  reorderAlerts: { type: Array, default: () => [] },
  reorderAlertsTotal: { type: Number, default: 0 },
```

### 8b. Add the tile

Insert this new `<div>` right after the closing `</div>` of the "Segregation-of-duties
note" block (the one with `v-if="can.ISSUE && !can.APPROVE_REQUESTS"`), i.e. immediately
before `<!-- Store areas -->`:
```html
    <!-- Reorder alerts -->
    <div v-if="reorderAlertsTotal" class="rounded-2xl border border-amber-500/20 bg-amber-500/[0.03] p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-amber-400/90 flex items-center gap-2">
          <Icon name="AlertTriangle" :size="15" /> Reorder Alerts
        </h3>
        <Link href="/store/items" class="text-[11px] font-bold text-amber-400 hover:text-amber-300">
          {{ reorderAlertsTotal }} item{{ reorderAlertsTotal === 1 ? '' : 's' }} — view catalog
        </Link>
      </div>
      <ul class="space-y-2">
        <li v-for="a in reorderAlerts" :key="a.id" class="flex items-center justify-between text-xs">
          <div class="min-w-0">
            <span class="font-mono text-amber-400/80">{{ a.code }}</span>
            <span class="text-slate-300 ml-2">{{ a.name_en }}</span>
            <span v-if="a.category" class="text-slate-600 ml-1">· {{ a.category }}</span>
          </div>
          <span class="font-mono text-slate-400 shrink-0 ml-4">{{ a.on_hand }} / {{ a.reorder_level }}</span>
        </li>
      </ul>
    </div>
```

**Verify:**
```bash
npm run build
```
Expected: build succeeds with no errors (see step 10 for the full build+verify pass).

---

## 9. Tests

**File (new):** `tests/Feature/InventoryCatalogReceivingTest.php`

Note: **there is no `InventoryBatchFactory`.** Construct `InventoryBatch` rows directly
with `InventoryCodeGenerator::grnNumber()`, exactly like the existing
`'deactivates rather than deletes a supplier with receipts on file'` test in
`InventoryFoundationTest.php` already does — do not try to call `InventoryBatch::factory()`.

```php
<?php

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryTrackingMode;
use App\Models\InventoryBatch;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStockMovement;
use App\Models\InventorySupplier;
use App\Models\InventoryUnit;
use App\Models\User;
use App\Support\Inventory\InventoryCodeGenerator;
use App\Support\StoreNavigation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StorePermissionsSeeder;

/**
 * Phase 2 — catalog & receiving: item CRUD, GRN receiving through StockLedger,
 * and the reorder-alert tile. See docs/inventory-management-design.md §5, §8.
 */
function storeUser(?string $role = null, array $permissions = []): User
{
    $user = User::factory()->create(['is_approved' => true, 'is_active' => true, 'password_changed' => true]);

    if ($role) {
        $user->assignRole($role);
    }

    foreach ($permissions as $permission) {
        $user->givePermissionTo($permission);
    }

    return $user;
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(StorePermissionsSeeder::class);
    $this->keeper = storeUser(StoreNavigation::ROLE);
});

// ---- Items --------------------------------------------------------------------

it('auto-generates the item code and never accepts one from the client', function () {
    $category = InventoryCategory::factory()->create(['code' => 'IT']);

    $this->actingAs($this->keeper)->post('/store/items', [
        'code' => 'HACKED-001', // must be ignored — code is server-generated
        'category_id' => $category->id,
        'name_en' => 'Dell Latitude 5420',
        'tracking_mode' => InventoryTrackingMode::Asset->value,
        'unit_of_measure' => 'piece',
        'status' => InventoryItemStatus::Active->value,
    ])->assertRedirect();

    $item = InventoryItem::where('name_en', 'Dell Latitude 5420')->first();

    expect($item)->not->toBeNull()
        ->and($item->code)->toBe('IT-00001')
        ->and($item->code)->not->toBe('HACKED-001');
});

it('archives rather than deletes an item with receiving history', function () {
    $item = InventoryItem::factory()->create();

    InventoryBatch::create([
        'grn_number' => InventoryCodeGenerator::grnNumber(),
        'item_id' => $item->id,
        'quantity_received' => 5,
        'purchase_date' => now()->toDateString(),
    ]);

    $this->actingAs($this->keeper)->delete("/store/items/{$item->id}");

    expect($item->fresh()->status)->toBe(InventoryItemStatus::Archived)
        ->and(InventoryItem::find($item->id))->not->toBeNull();
});

it('deletes an item with no transaction history', function () {
    $item = InventoryItem::factory()->create();

    $this->actingAs($this->keeper)->delete("/store/items/{$item->id}");

    expect(InventoryItem::find($item->id))->toBeNull();
});

it('refuses to create an item without the catalog permission', function () {
    $viewer = storeUser(permissions: ['view inventory']);
    $category = InventoryCategory::factory()->create();

    $this->actingAs($viewer)->post('/store/items', [
        'category_id' => $category->id,
        'name_en' => 'Should not be created',
        'tracking_mode' => 'consumable',
        'unit_of_measure' => 'piece',
        'status' => 'active',
    ])->assertForbidden();

    expect(InventoryItem::where('name_en', 'Should not be created')->exists())->toBeFalse();
});

// ---- Receiving ------------------------------------------------------------------

it('receives a consumable and posts one inward ledger movement', function () {
    $item = InventoryItem::factory()->create(['tracking_mode' => InventoryTrackingMode::Consumable]);
    $location = InventoryLocation::factory()->create();
    $supplier = InventorySupplier::factory()->create();

    $this->actingAs($this->keeper)->post('/store/receipts', [
        'item_id' => $item->id,
        'supplier_id' => $supplier->id,
        'location_id' => $location->id,
        'quantity_received' => 100,
        'unit_cost' => 25,
        'purchase_date' => now()->toDateString(),
    ])->assertRedirect();

    $batch = InventoryBatch::where('item_id', $item->id)->first();

    expect($item->fresh()->onHand())->toBe(100.0)
        ->and((float) $batch->total_cost)->toBe(2500.0)
        ->and(InventoryUnit::where('item_id', $item->id)->count())->toBe(0);
});

it('receives an asset item as one unit per quantity, each with its own tag', function () {
    $item = InventoryItem::factory()->asset()->create();

    $this->actingAs($this->keeper)->post('/store/receipts', [
        'item_id' => $item->id,
        'quantity_received' => 3,
        'unit_cost' => 40000,
        'purchase_date' => now()->toDateString(),
    ])->assertRedirect();

    $units = InventoryUnit::where('item_id', $item->id)->get();

    expect($units)->toHaveCount(3)
        ->and($units->pluck('asset_tag')->unique())->toHaveCount(3)
        ->and($item->fresh()->onHand())->toBe(3.0);
});

it('rejects a fractional quantity for an asset-tracked item', function () {
    $item = InventoryItem::factory()->asset()->create();

    $this->actingAs($this->keeper)->post('/store/receipts', [
        'item_id' => $item->id,
        'quantity_received' => 2.5,
        'purchase_date' => now()->toDateString(),
    ])->assertSessionHasErrors('quantity_received');

    expect(InventoryUnit::where('item_id', $item->id)->count())->toBe(0);
});

it('rejects a zero or negative quantity received', function () {
    $item = InventoryItem::factory()->create();

    $this->actingAs($this->keeper)->post('/store/receipts', [
        'item_id' => $item->id,
        'quantity_received' => 0,
        'purchase_date' => now()->toDateString(),
    ])->assertSessionHasErrors('quantity_received');
});

it('refuses to record a receipt without the receive permission', function () {
    $viewer = storeUser(permissions: ['view inventory']);
    $item = InventoryItem::factory()->create();

    $this->actingAs($viewer)->post('/store/receipts', [
        'item_id' => $item->id,
        'quantity_received' => 5,
        'purchase_date' => now()->toDateString(),
    ])->assertForbidden();

    expect($item->fresh()->onHand())->toBe(0.0);
});

// ---- Dashboard reorder alerts -----------------------------------------------------

it('surfaces items at or below their reorder level on the store dashboard', function () {
    $low = InventoryItem::factory()->withReorderLevel(20)->create(['name_en' => 'Printer Paper']);
    InventoryStockMovement::factory()->for($low, 'item')->receipt(5)->create();

    $healthy = InventoryItem::factory()->withReorderLevel(20)->create(['name_en' => 'Whiteboard Markers']);
    InventoryStockMovement::factory()->for($healthy, 'item')->receipt(90)->create();

    $this->actingAs($this->keeper)->get('/store')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('reorderAlertsTotal', 1)
            ->has('reorderAlerts', 1)
            ->where('reorderAlerts.0.name_en', 'Printer Paper'));
});
```

**Verify:**
```bash
php artisan test tests/Feature/InventoryCatalogReceivingTest.php
```
Expected: all tests pass (12 tests). If any fail, read the failure message — do not
change the test's expectations to make it pass; the test encodes an invariant from
`docs/inventory-management-design.md` §5, so a failure means the implementation is wrong,
not the test.

Then run the full suite to confirm nothing else broke:
```bash
php artisan test
```

---

## 10. Frontend build

Run once, after every file above is in place and the tests pass:
```bash
npm run build
```
Expected: `✓ built in <N>s` with no errors. Then stage the rebuilt assets:
```bash
git status --short public/build
```
Expected: new/modified files under `public/build/` — these must be committed in the same
commit as the source changes (see CLAUDE.md — "Critical: assets are committed").

---

## 11. Documentation updates

**File (edit):** `docs/inventory-management-design.md`

### 11a. Status line near the top

Find:
```
Status: **Phase 0 (access layer) and Phase 1 (foundation) implemented.** Phases 2–6
specified below.
```
Replace with:
```
Status: **Phases 0–2 implemented** (access layer, foundation, catalog & receiving).
Phases 3–6 specified below.
```

### 11b. §8 delivery table

Find the `| **2 — Catalog & receiving** |` row and change its `Ships` cell from
`"what do we own, how much, where"` to `*done* — "what do we own, how much, where"`,
matching how Phase 0 and Phase 1's rows already end with `*done*`.

### 11c. New §9 addendum

After the existing "## 9. What Phase 1 shipped" section (and before "## 10. Build &
deploy note"), add:
```markdown
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
```

**Verify:** read the file back and confirm the three edits landed; no command to run.

---

## 12. Final acceptance checklist

Run through this by hand, signed in as a user with the `Store Keeper` role:

- [ ] `/store` loads; the "Delivery phases" list shows Phase 2 marked done, Phase 3 marked next.
- [ ] `/store` shows a "Reorder Alerts" tile when an item's on-hand is at or below its reorder level, and the tile is absent when none are.
- [ ] `/store/items` loads; "+ New Item" opens a modal; creating an item shows a success message and the new row appears with a system-generated code like `IT-00001`.
- [ ] Uploading a photo on create shows the thumbnail on the row afterward.
- [ ] Editing an item and picking a different category updates the tracking-mode default only when creating, not when editing an existing item.
- [ ] Attaching a document (invoice/warranty/manual) via the edit modal's "Attachments" panel appears in the list and can be removed.
- [ ] Deleting a never-received item removes it; deleting an item that has a receipt on file archives it instead (status badge changes to Archived, row stays visible).
- [ ] `/store/receipts` loads; "+ Record Receipt" opens a modal; picking an asset-tracked item shows the "one asset tag per unit" note and forces a whole-number quantity.
- [ ] Recording a receipt for a consumable item increases that item's on-hand by the quantity received, visible back on `/store/items`.
- [ ] Recording a receipt for an asset-tracked item creates that many rows in `inventory_units`, each with a distinct `asset_tag` (spot-check via `php artisan tinker`).
- [ ] Signed in as a user who holds `view inventory` but not `manage inventory catalog` / `receive inventory`: `/store/items` and `/store/receipts` are readable (no create button shown), and `POST`ing directly to either write endpoint returns 403.
- [ ] `php artisan test` — full suite green.
- [ ] `npm run build` — clean, and `public/build` changes are part of the same commit as the source changes.

---

## Appendix — pitfalls specific to this phase

- **Do not let `InventoryStockMovement` rows be created anywhere but inside
  `StockLedger`.** The model throws on `update()`/`delete()` by design; if you find
  yourself calling `InventoryStockMovement::create()` from a controller, stop — route it
  through the ledger instead, even if it feels like "just this once."
- **The item's `code` is never client input.** If `storeItem`/`updateItem` validation
  ever grows a `code` field, that's a regression — `InventoryCodeGenerator::itemCode()` is
  the only source.
- **`InventoryBatch` has no factory.** Build it with `InventoryBatch::create([...])` plus
  `InventoryCodeGenerator::grnNumber()` in tests, not `InventoryBatch::factory()`.
- **File uploads on an update must spoof `_method: 'put'` over a POST**, exactly as
  `resources/views/oauth/authorize.blade.php` already does for its `DELETE` form via
  `@method('DELETE')`. Don't rely on a raw `form.put()` with a file attached.
- **`assertSufficientStock` and the whole outward path are unused until Phase 3.** Phase 2
  only calls `receive()`, which is purely inward, so the negative-stock guard has no
  exercising test yet in this phase — that is expected, not a gap to fill here. Phase 3's
  `issue()` is where that guard gets its first real test.
- **`onHand()` runs one query per row in the Items list.** This is deliberate — see the
  comment in `CatalogController::present()`. Do not "optimize" it by adding a cached
  balance column; that is explicitly Phase 6 scope in
  `docs/inventory-management-design.md` §1, gated on "only if measured queries justify
  it."
