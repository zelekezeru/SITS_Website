<?php

use App\Enums\DepreciationMethod;
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
function catalogStoreUser(?string $role = null, array $permissions = []): User
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
    $this->keeper = catalogStoreUser(StoreNavigation::ROLE);
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
    $viewer = catalogStoreUser(permissions: ['view inventory']);
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
    $item = InventoryItem::factory()->asset()->create([
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_months' => 60,
    ]);

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
    $item = InventoryItem::factory()->asset()->create([
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_months' => 60,
    ]);

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
    $viewer = catalogStoreUser(permissions: ['view inventory']);
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
