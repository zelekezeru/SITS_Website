<?php

use App\Enums\DepreciationMethod;
use App\Enums\InventoryCondition;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryRequestStatus;
use App\Enums\InventoryTrackingMode;
use App\Enums\InventoryUnitStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequest;
use App\Models\InventoryRequestLine;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Models\User;
use App\Services\Inventory\StockLedger;
use App\Support\StoreNavigation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StorePermissionsSeeder;

/**
 * Phase 3 — Issue & Requisition: Requisition maker-checker workflow, issue vouchers,
 * returns, and inter-location transfers.
 */
function reqStoreUser(?string $role = null, array $permissions = []): User
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
    $this->keeper = reqStoreUser(StoreNavigation::ROLE);
    $this->ops = reqStoreUser('Operational Manager');
    $this->employeeUser = reqStoreUser('Employee');
    $this->employee = Employee::create([
        'user_id' => $this->employeeUser->id,
        'staff_no' => 'EMP-001',
        'first_name' => 'John',
        'father_name' => 'Doe',
        'full_name_en' => 'John Doe',
        'employment_type' => 'full_time',
        'hire_date' => now()->toDateString(),
    ]);
});

// ---- Requisitions (Maker-Checker) --------------------------------------------

it('allows an employee to create and submit a requisition with auto-generated REQ number', function () {
    $item = InventoryItem::factory()->create(['name_en' => 'Ballpoint Pens']);
    $dept = Department::create(['name_en' => 'Faculty Office', 'is_active' => true]);

    $this->actingAs($this->employeeUser)->post('/store/requests', [
        'requested_by_employee_id' => $this->employee->id,
        'department_id' => $dept->id,
        'purpose' => 'Office supplies for faculty',
        'submit_now' => true,
        'lines' => [
            ['item_id' => $item->id, 'quantity_requested' => 10, 'note' => 'Blue ink'],
        ],
    ])->assertRedirect();

    $req = InventoryRequest::where('purpose', 'Office supplies for faculty')->first();

    expect($req)->not->toBeNull()
        ->and($req->request_number)->toMatch('/^REQ-\d{4}-\d{4}$/')
        ->and($req->status)->toBe(InventoryRequestStatus::Submitted)
        ->and($req->lines)->toHaveCount(1)
        ->and((float) $req->lines->first()->quantity_requested)->toBe(10.0);
});

it('strictly withholds requisition approval from the Store Keeper (Segregation of Duties)', function () {
    $item = InventoryItem::factory()->create();
    $req = InventoryRequest::create([
        'request_number' => 'REQ-2026-0001',
        'status' => InventoryRequestStatus::Submitted,
        'purpose' => 'Test',
        'requested_by_employee_id' => $this->employee->id,
    ]);
    $line = InventoryRequestLine::create([
        'request_id' => $req->id,
        'item_id' => $item->id,
        'quantity_requested' => 5,
    ]);

    // Store keeper tries to approve
    $this->actingAs($this->keeper)->post("/store/requests/{$req->id}/approve", [
        'lines' => [
            ['id' => $line->id, 'quantity_approved' => 5],
        ],
    ])->assertForbidden();

    expect($req->fresh()->status)->toBe(InventoryRequestStatus::Submitted);
});

it('allows Operations to approve a requisition with custom approved quantities', function () {
    $item = InventoryItem::factory()->create();
    $req = InventoryRequest::create([
        'request_number' => 'REQ-2026-0002',
        'status' => InventoryRequestStatus::Submitted,
        'purpose' => 'Test',
        'requested_by_employee_id' => $this->employee->id,
    ]);
    $line = InventoryRequestLine::create([
        'request_id' => $req->id,
        'item_id' => $item->id,
        'quantity_requested' => 10,
    ]);

    // Operations approves 6 instead of 10
    $this->actingAs($this->ops)->post("/store/requests/{$req->id}/approve", [
        'lines' => [
            ['id' => $line->id, 'quantity_approved' => 6],
        ],
    ])->assertRedirect();

    $fresh = $req->fresh(['lines']);
    expect($fresh->status)->toBe(InventoryRequestStatus::Approved)
        ->and((float) $fresh->lines->first()->quantity_approved)->toBe(6.0)
        ->and($fresh->lines->first()->wasTrimmed())->toBeTrue();
});

it('allows Store Keeper to issue stock against an approved requisition and marks it fulfilled', function () {
    $item = InventoryItem::factory()->create();
    $location = InventoryLocation::factory()->create();

    // Initial stock receipt of 20
    app(StockLedger::class)->receive([
        'item_id' => $item->id,
        'quantity_received' => 20,
        'purchase_date' => now()->toDateString(),
        'location_id' => $location->id,
    ], $this->keeper);

    $req = InventoryRequest::create([
        'request_number' => 'REQ-2026-0003',
        'status' => InventoryRequestStatus::Approved,
        'purpose' => 'Test',
        'requested_by_employee_id' => $this->employee->id,
    ]);
    $line = InventoryRequestLine::create([
        'request_id' => $req->id,
        'item_id' => $item->id,
        'quantity_requested' => 5,
        'quantity_approved' => 5,
        'quantity_issued' => 0,
    ]);

    $this->actingAs($this->keeper)->post("/store/requests/{$req->id}/issue", [
        'issues' => [
            ['line_id' => $line->id, 'quantity' => 5, 'from_location_id' => $location->id],
        ],
    ])->assertRedirect();

    $freshReq = $req->fresh(['lines']);
    expect($freshReq->status)->toBe(InventoryRequestStatus::Fulfilled)
        ->and((float) $freshReq->lines->first()->quantity_issued)->toBe(5.0)
        ->and($item->fresh()->onHand())->toBe(15.0);
});

it('rejects an issuance that exceeds the approved quantity (Invariant 5)', function () {
    $item = InventoryItem::factory()->create();
    $location = InventoryLocation::factory()->create();

    app(StockLedger::class)->receive([
        'item_id' => $item->id,
        'quantity_received' => 50,
        'purchase_date' => now()->toDateString(),
        'location_id' => $location->id,
    ], $this->keeper);

    $req = InventoryRequest::create([
        'request_number' => 'REQ-2026-0004',
        'status' => InventoryRequestStatus::Approved,
        'purpose' => 'Test',
        'requested_by_employee_id' => $this->employee->id,
    ]);
    $line = InventoryRequestLine::create([
        'request_id' => $req->id,
        'item_id' => $item->id,
        'quantity_requested' => 5,
        'quantity_approved' => 5,
        'quantity_issued' => 0,
    ]);

    // Attempt to issue 6 when only 5 approved
    $this->actingAs($this->keeper)->post("/store/requests/{$req->id}/issue", [
        'issues' => [
            ['line_id' => $line->id, 'quantity' => 6, 'from_location_id' => $location->id],
        ],
    ])->assertSessionHasErrors();

    expect($line->fresh()->quantity_issued)->toBe('0.000');
});

// ---- Direct Issues & Negative Stock Guard -------------------------------------

it('rejects an issue that would drive on-hand balance negative (Invariant 1)', function () {
    $item = InventoryItem::factory()->create();
    $location = InventoryLocation::factory()->create();

    app(StockLedger::class)->receive([
        'item_id' => $item->id,
        'quantity_received' => 3,
        'purchase_date' => now()->toDateString(),
        'location_id' => $location->id,
    ], $this->keeper);

    $this->actingAs($this->keeper)->post('/store/issues', [
        'item_id' => $item->id,
        'quantity' => 5, // exceeds 3 on hand
        'from_location_id' => $location->id,
    ])->assertSessionHasErrors();

    expect($item->fresh()->onHand())->toBe(3.0);
});

// ---- Returns -----------------------------------------------------------------

it('records a return to store and increments available on-hand stock', function () {
    $item = InventoryItem::factory()->create();
    $location = InventoryLocation::factory()->create();

    app(StockLedger::class)->receive([
        'item_id' => $item->id,
        'quantity_received' => 10,
        'purchase_date' => now()->toDateString(),
        'location_id' => $location->id,
    ], $this->keeper);

    // Direct issue 4
    $this->actingAs($this->keeper)->post('/store/issues', [
        'item_id' => $item->id,
        'quantity' => 4,
        'from_location_id' => $location->id,
        'employee_id' => $this->employee->id,
    ])->assertRedirect();

    expect($item->fresh()->onHand())->toBe(6.0);

    // Return 2 back to store
    $this->actingAs($this->keeper)->post('/store/returns', [
        'item_id' => $item->id,
        'quantity' => 2,
        'to_location_id' => $location->id,
        'employee_id' => $this->employee->id,
        'reason' => 'Unused items returned',
    ])->assertRedirect();

    expect($item->fresh()->onHand())->toBe(8.0);
});

// ---- Transfers ---------------------------------------------------------------

it('moves stock between locations with paired TransferOut and TransferIn movements', function () {
    $item = InventoryItem::factory()->create();
    $locA = InventoryLocation::factory()->create(['name' => 'Main Store']);
    $locB = InventoryLocation::factory()->create(['name' => 'Seminary Lab']);

    app(StockLedger::class)->receive([
        'item_id' => $item->id,
        'quantity_received' => 10,
        'purchase_date' => now()->toDateString(),
        'location_id' => $locA->id,
    ], $this->keeper);

    expect($item->onHand($locA->id))->toBe(10.0)
        ->and($item->onHand($locB->id))->toBe(0.0);

    $this->actingAs($this->keeper)->post('/store/transfers', [
        'item_id' => $item->id,
        'from_location_id' => $locA->id,
        'to_location_id' => $locB->id,
        'quantity' => 4,
        'reason' => 'Supply lab with materials',
    ])->assertRedirect();

    expect($item->fresh()->onHand($locA->id))->toBe(6.0)
        ->and($item->fresh()->onHand($locB->id))->toBe(4.0)
        ->and($item->fresh()->onHand())->toBe(10.0); // Total unchanged
});
