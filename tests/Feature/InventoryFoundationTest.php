<?php

use App\Enums\DepreciationMethod;
use App\Enums\InventoryLocationType;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryTrackingMode;
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
 * Phase 1 — the inventory foundation: schema, models, derived quantities and the
 * reference-data CRUD. See docs/inventory-management-design.md §1, §2, §4.
 */
function inventoryUser(?string $role = null): User
{
    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password_changed' => true,
    ]);

    if ($role) {
        $user->assignRole($role);
    }

    return $user;
}

// ---- The ledger is the source of truth -------------------------------------

it('derives quantity on hand by summing the signed ledger', function () {
    $item = InventoryItem::factory()->create();

    InventoryStockMovement::factory()->for($item, 'item')->receipt(100)->create();
    InventoryStockMovement::factory()->for($item, 'item')->receipt(50)->create();
    InventoryStockMovement::factory()->for($item, 'item')->issue(30)->create();

    expect($item->onHand())->toBe(120.0);
});

it('scopes quantity on hand to a location subtree', function () {
    $item = InventoryItem::factory()->create();
    $store = InventoryLocation::factory()->create();
    $shelf = InventoryLocation::factory()->childOf($store)->of(InventoryLocationType::Shelf)->create();
    $elsewhere = InventoryLocation::factory()->create();

    InventoryStockMovement::factory()->for($item, 'item')->receipt(40, $shelf)->create();
    InventoryStockMovement::factory()->for($item, 'item')->receipt(10, $elsewhere)->create();
    InventoryStockMovement::factory()->for($item, 'item')->issue(15, $shelf)->create();

    // The store rolls its shelf up; the other location is untouched by both.
    expect($item->onHand($store->id))->toBe(25.0)
        ->and($item->onHand($shelf->id))->toBe(25.0)
        ->and($item->onHand($elsewhere->id))->toBe(10.0)
        ->and($item->onHand())->toBe(35.0);
});

it('refuses to edit or delete a movement so history cannot be rewritten', function () {
    $movement = InventoryStockMovement::factory()->receipt(10)->create();

    expect(fn () => $movement->update(['quantity' => 999]))
        ->toThrow(LogicException::class, 'append-only');

    expect(fn () => $movement->delete())
        ->toThrow(LogicException::class, 'cannot be deleted');

    expect((float) $movement->fresh()->quantity)->toBe(10.0);
});

it('lets the movement type decide the sign, never the caller', function () {
    expect(InventoryMovementType::Receipt->direction())->toBe(1)
        ->and(InventoryMovementType::Issue->direction())->toBe(-1)
        ->and(InventoryMovementType::TransferOut->isOutward())->toBeTrue()
        ->and(InventoryMovementType::TransferIn->isInward())->toBeTrue()
        // An adjustment can go either way, so the caller supplies the sign.
        ->and(InventoryMovementType::Adjustment->isSignedByCaller())->toBeTrue()
        ->and(InventoryMovementType::Adjustment->requiresAdjustPermission())->toBeTrue();
});

// ---- Reorder ---------------------------------------------------------------

it('finds items at or below their reorder level in SQL', function () {
    $low = InventoryItem::factory()->withReorderLevel(20)->create();
    $healthy = InventoryItem::factory()->withReorderLevel(20)->create();
    $noPolicy = InventoryItem::factory()->create(); // reorder_level 0 — never alerts

    InventoryStockMovement::factory()->for($low, 'item')->receipt(15)->create();
    InventoryStockMovement::factory()->for($healthy, 'item')->receipt(90)->create();
    InventoryStockMovement::factory()->for($noPolicy, 'item')->issue(5)->create();

    $flagged = InventoryItem::needingReorder()->pluck('id');

    expect($flagged)->toContain($low->id)
        ->and($flagged)->not->toContain($healthy->id)
        ->and($flagged)->not->toContain($noPolicy->id)
        // The scope and the per-model check must agree.
        ->and($low->needsReorder())->toBeTrue()
        ->and($healthy->needsReorder())->toBeFalse();
});

it('stops alerting once an item is discontinued', function () {
    $item = InventoryItem::factory()->withReorderLevel(20)->discontinued()->create();
    InventoryStockMovement::factory()->for($item, 'item')->receipt(1)->create();

    expect($item->needsReorder())->toBeFalse()
        ->and(InventoryItem::needingReorder()->pluck('id'))->not->toContain($item->id);
});

// ---- Trees -----------------------------------------------------------------

it('builds a readable path up the category tree', function () {
    $root = InventoryCategory::factory()->create(['name_en' => 'Equipment']);
    $mid = InventoryCategory::factory()->childOf($root)->create(['name_en' => 'IT']);
    $leaf = InventoryCategory::factory()->childOf($mid)->create(['name_en' => 'Laptops']);

    expect($leaf->fullPath())->toBe('Equipment › IT › Laptops')
        ->and($root->descendantIds())->toEqualCanonicalizing([$root->id, $mid->id, $leaf->id]);
});

it('inherits a custodian from the nearest ancestor that names one', function () {
    $employee = App\Models\Employee::create([
        'user_id' => User::factory()->create()->id,
        'staff_no' => 'STORE-1',
        'full_name_en' => 'Store Keeper',
    ]);

    $store = InventoryLocation::factory()->create(['custodian_employee_id' => $employee->id]);
    $room = InventoryLocation::factory()->childOf($store)->of(InventoryLocationType::Room)->create();
    $shelf = InventoryLocation::factory()->childOf($room)->of(InventoryLocationType::Shelf)->create();

    expect($shelf->effectiveCustodian()?->id)->toBe($employee->id);
});

it('knows which location types can actually hold stock', function () {
    InventoryLocation::factory()->of(InventoryLocationType::Building)->create();
    InventoryLocation::factory()->of(InventoryLocationType::Shelf)->create();
    InventoryLocation::factory()->of(InventoryLocationType::Bin)->create();

    expect(InventoryLocation::storable()->count())->toBe(2)
        ->and(InventoryLocationType::Building->isStorable())->toBeFalse()
        ->and(InventoryLocationType::Shelf->isStorable())->toBeTrue();
});

// ---- Codes -----------------------------------------------------------------

it('generates sequential, prefixed codes', function () {
    $category = InventoryCategory::factory()->create(['code' => 'IT']);

    $first = InventoryCodeGenerator::itemCode($category);
    InventoryItem::factory()->create(['code' => $first, 'category_id' => $category->id]);
    $second = InventoryCodeGenerator::itemCode($category);

    expect($first)->toBe('IT-00001')
        ->and($second)->toBe('IT-00002')
        ->and(InventoryCodeGenerator::supplierCode())->toBe('SUP-0001')
        ->and(InventoryCodeGenerator::grnNumber())->toBe('GRN-'.now()->year.'-0001')
        ->and(InventoryCodeGenerator::locationCode('Main Campus'))->toBe('LOC-MC-0001');
});

it('keeps counting past the nine-to-ten digit boundary', function () {
    // Ordering codes as plain strings would put IT-00009 after IT-00010 and
    // hand out a duplicate; the generator sorts by length first.
    $category = InventoryCategory::factory()->create(['code' => 'IT']);

    foreach (range(1, 10) as $i) {
        InventoryItem::factory()->create([
            'code' => InventoryCodeGenerator::itemCode($category),
            'category_id' => $category->id,
        ]);
    }

    expect(InventoryCodeGenerator::itemCode($category))->toBe('IT-00011')
        ->and(InventoryItem::where('category_id', $category->id)->count())->toBe(10);
});

it('counts soft-deleted rows toward the sequence so codes never collide', function () {
    $supplier = InventorySupplier::factory()->create(['code' => InventoryCodeGenerator::supplierCode()]);
    expect($supplier->code)->toBe('SUP-0001');

    $supplier->delete();

    // Its unique code survives the soft delete, so reusing SUP-0001 would fail.
    expect(InventoryCodeGenerator::supplierCode())->toBe('SUP-0002');
});

// ---- Depreciation ----------------------------------------------------------

it('depreciates an asset straight line and never below salvage', function () {
    $unit = InventoryUnit::factory()->depreciating(cost: 48000, usefulLifeMonths: 48, monthsAgo: 12)->create();

    // A quarter of the way through a four-year life.
    expect($unit->accumulatedDepreciation())->toBe(12000.0)
        ->and($unit->bookValue())->toBe(36000.0);

    $expired = InventoryUnit::factory()->depreciating(cost: 10000, usefulLifeMonths: 12, monthsAgo: 60)->create();

    expect($expired->accumulatedDepreciation())->toBe(10000.0)
        ->and($expired->bookValue())->toBe(0.0);
});

it('leaves non-depreciating assets at cost', function () {
    expect(DepreciationMethod::None->accumulated(5000, 0, 48, 24))->toBe(0.0);
});

it('falls back to the category depreciation policy when the item sets none', function () {
    $category = InventoryCategory::factory()->assets()->create();
    $item = InventoryItem::factory()->asset()->create([
        'category_id' => $category->id,
        'depreciation_method' => null,
        'useful_life_months' => null,
    ]);

    expect($item->effectiveDepreciationMethod())->toBe(DepreciationMethod::StraightLine)
        ->and($item->effectiveUsefulLifeMonths())->toBe(48);
});

// ---- Reference data CRUD ---------------------------------------------------

describe('reference data pages', function () {
    beforeEach(function () {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(StorePermissionsSeeder::class);
        $this->keeper = inventoryUser(StoreNavigation::ROLE);
    });

    it('renders categories, suppliers and locations for the store keeper', function () {
        $this->actingAs($this->keeper)->get('/store/categories')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Store/Categories')->where('can.manage', true));

        $this->actingAs($this->keeper)->get('/store/suppliers')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Store/Suppliers'));

        $this->actingAs($this->keeper)->get('/store/locations')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Store/Locations'));
    });

    it('creates a category', function () {
        $this->actingAs($this->keeper)->post('/store/categories', [
            'code' => 'IT',
            'name_en' => 'IT Equipment',
            'tracking_mode' => InventoryTrackingMode::Asset->value,
            'default_depreciation_method' => DepreciationMethod::StraightLine->value,
            'default_useful_life_months' => 48,
            'is_active' => true,
        ])->assertRedirect();

        $category = InventoryCategory::where('code', 'IT')->first();

        expect($category)->not->toBeNull()
            ->and($category->tracking_mode)->toBe(InventoryTrackingMode::Asset);
    });

    it('rejects a category code that is not plain alphanumerics', function () {
        $this->actingAs($this->keeper)->post('/store/categories', [
            'code' => 'IT-01',
            'name_en' => 'Bad code',
            'tracking_mode' => 'consumable',
            'default_depreciation_method' => 'none',
        ])->assertSessionHasErrors('code');
    });

    it('refuses to make a category its own descendant', function () {
        $parent = InventoryCategory::factory()->create();
        $child = InventoryCategory::factory()->childOf($parent)->create();

        $this->actingAs($this->keeper)->put("/store/categories/{$parent->id}", [
            'parent_id' => $child->id,
            'code' => $parent->code,
            'name_en' => $parent->name_en,
            'tracking_mode' => $parent->tracking_mode->value,
            'default_depreciation_method' => $parent->default_depreciation_method->value,
        ])->assertSessionHasErrors('parent_id');

        expect($parent->fresh()->parent_id)->toBeNull();
    });

    it('will not delete a category that still holds items', function () {
        $category = InventoryCategory::factory()->create();
        InventoryItem::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->keeper)->delete("/store/categories/{$category->id}");

        expect(InventoryCategory::find($category->id))->not->toBeNull();
    });

    it('auto-assigns a supplier code on create', function () {
        $this->actingAs($this->keeper)->post('/store/suppliers', [
            'name' => 'Addis Stationery PLC',
            'tin' => '0001234567',
            'is_active' => true,
        ])->assertRedirect();

        expect(InventorySupplier::where('name', 'Addis Stationery PLC')->value('code'))->toBe('SUP-0001');
    });

    it('deactivates rather than deletes a supplier with receipts on file', function () {
        $supplier = InventorySupplier::factory()->create();
        App\Models\InventoryBatch::create([
            'grn_number' => InventoryCodeGenerator::grnNumber(),
            'item_id' => InventoryItem::factory()->create()->id,
            'supplier_id' => $supplier->id,
            'quantity_received' => 10,
            'purchase_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->keeper)->delete("/store/suppliers/{$supplier->id}");

        expect($supplier->fresh())->not->toBeNull()
            ->and($supplier->fresh()->is_active)->toBeFalse();
    });

    it('deactivates rather than deletes a location with stock history', function () {
        $location = InventoryLocation::factory()->create();
        InventoryStockMovement::factory()->receipt(5, $location)->create();

        $this->actingAs($this->keeper)->delete("/store/locations/{$location->id}");

        expect($location->fresh())->not->toBeNull()
            ->and($location->fresh()->is_active)->toBeFalse();
    });

    it('blocks writes from a viewer who only holds view inventory', function () {
        $finance = inventoryUser('Finance Officer');

        // Finance can read the catalog…
        $this->actingAs($finance)->get('/store/categories')->assertOk();

        // …but not change it.
        $this->actingAs($finance)->post('/store/categories', [
            'code' => 'XX',
            'name_en' => 'Nope',
            'tracking_mode' => 'consumable',
            'default_depreciation_method' => 'none',
        ])->assertForbidden();

        $this->actingAs($finance)->post('/store/suppliers', ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($finance)->post('/store/locations', ['name' => 'Nope', 'type' => 'room'])->assertForbidden();
    });
});
