<?php

namespace App\Services\Inventory;

use App\Enums\InventoryCondition;
use App\Enums\InventoryMaintenanceType;
use App\Enums\InventoryUnitStatus;
use App\Models\Employee;
use App\Models\InventoryAssetAssignment;
use App\Models\InventoryMaintenanceLog;
use App\Models\InventoryUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Everything that happens to a serialized asset *after* it is received: custody
 * handovers, deployment to a room, maintenance episodes and the register edits
 * that keep depreciation honest.
 *
 * Custody changes are not merely status flips — an asset leaving the store is a
 * stock movement, so every method here that moves one physically delegates to
 * StockLedger rather than writing the ledger itself. That is what keeps
 * `on_hand` reconciled with the asset register: there is exactly one writer.
 *
 * Invariant 3 (one location, at most one holder, and issuing an already-issued
 * unit fails) is enforced here, under a locked read of the unit, because the
 * check and the write have to be atomic to mean anything.
 *
 * @see docs/inventory-management-design.md §5
 */
class AssetRegistry
{
    public function __construct(private readonly StockLedger $ledger) {}

    /**
     * Hand an asset to an employee. Posts the issue movement, opens the custody
     * record and stamps the unit's denormalized pointer — one transaction, so a
     * unit can never end up issued without a custody row to say to whom.
     *
     * @param  array<string, mixed>  $data
     */
    public function assign(array $data, User $performedBy): InventoryAssetAssignment
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $unit = $this->lockUnit((int) $data['unit_id']);

            $this->assertAssignable($unit);

            if (empty($data['employee_id'])) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Select the employee taking custody of this asset.',
                ]);
            }

            $this->ledger->issue([
                'item_id' => $unit->item_id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'employee_id' => (int) $data['employee_id'],
                'from_location_id' => $unit->current_location_id,
                'due_at' => $data['due_at'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'occurred_at' => $data['issued_at'] ?? now(),
                'reason' => $data['purpose'] ?? 'Asset handover',
                'notes' => $data['notes'] ?? null,
            ], $performedBy);

            /** @var InventoryAssetAssignment $assignment */
            $assignment = $unit->fresh()->openAssignment;

            // The ledger records custody with the unit's condition as it stood.
            // A handover that notes a different condition overrides that — the
            // person signing the slip is the one who actually looked at it.
            if (! empty($data['condition_out'])) {
                $condition = InventoryCondition::from($data['condition_out']);
                $assignment->update(['condition_out' => $condition]);
                $unit->update(['condition' => $condition]);
            }

            return $assignment->fresh(['unit.item', 'employee', 'issuedBy']);
        });
    }

    /**
     * Take an asset back into store. Closes the custody record with the
     * condition it came back in, which is what makes damage attributable rather
     * than merely noticed.
     *
     * @param  array<string, mixed>  $data
     */
    public function receiveBack(array $data, User $performedBy): InventoryAssetAssignment
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $unit = $this->lockUnit((int) $data['unit_id']);

            $assignment = $unit->openAssignment;

            if (! $assignment) {
                throw ValidationException::withMessages([
                    'unit_id' => "Asset {$unit->asset_tag} is not currently out with anyone.",
                ]);
            }

            $this->ledger->returnStock([
                'item_id' => $unit->item_id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'to_location_id' => $data['to_location_id'] ?? null,
                'employee_id' => $assignment->employee_id,
                'condition' => $data['condition_in'] ?? null,
                'occurred_at' => $data['returned_at'] ?? now(),
                'reason' => 'Asset returned to store',
                'notes' => $data['notes'] ?? null,
            ], $performedBy);

            $assignment->refresh();

            if (! empty($data['notes'])) {
                $assignment->update(['notes' => trim(($assignment->notes ?? '')."\n".$data['notes'])]);
            }

            // A unit that comes back unserviceable is not available stock: leaving
            // it "in store" would offer it up on the next issue screen.
            if ($assignment->condition_in?->isEndOfLife()) {
                $unit->update(['status' => InventoryUnitStatus::UnderMaintenance]);
            }

            return $assignment->fresh(['unit.item', 'employee', 'receivedBackBy']);
        });
    }

    /**
     * Install an asset somewhere rather than hand it to a person — a projector
     * bolted to a lecture-hall ceiling has a location but no custodian.
     *
     * Still a stock movement out of the store: the shelf no longer holds it.
     *
     * @param  array<string, mixed>  $data
     */
    public function deploy(array $data, User $performedBy): InventoryUnit
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $unit = $this->lockUnit((int) $data['unit_id']);

            $this->assertAssignable($unit);

            $toLocationId = (int) ($data['to_location_id'] ?? 0);

            if ($toLocationId <= 0) {
                throw ValidationException::withMessages([
                    'to_location_id' => 'Select the room or office the asset is being installed in.',
                ]);
            }

            if ($unit->current_location_id === null) {
                throw ValidationException::withMessages([
                    'unit_id' => "Asset {$unit->asset_tag} has no current location to move it from.",
                ]);
            }

            $this->ledger->transfer([
                'item_id' => $unit->item_id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'from_location_id' => $unit->current_location_id,
                'to_location_id' => $toLocationId,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'reason' => $data['purpose'] ?? 'Deployed in place',
                'notes' => $data['notes'] ?? null,
            ], $performedBy);

            $unit->refresh()->update(['status' => InventoryUnitStatus::Deployed]);

            return $unit->fresh(['item', 'location']);
        });
    }

    /**
     * Open a maintenance episode. The asset leaves service but not the register —
     * it is still ours, so no stock movement is posted; only its availability
     * changes.
     *
     * @param  array<string, mixed>  $data
     */
    public function startMaintenance(array $data, User $performedBy): InventoryMaintenanceLog
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $unit = $this->lockUnit((int) $data['unit_id']);

            if (! $unit->acceptsMovements()) {
                throw ValidationException::withMessages([
                    'unit_id' => "Asset {$unit->asset_tag} is {$unit->status->label()} and is no longer serviced.",
                ]);
            }

            $log = InventoryMaintenanceLog::create([
                'unit_id' => $unit->id,
                'type' => $data['type'] ?? InventoryMaintenanceType::Repair->value,
                'reported_by' => $performedBy->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'cost' => $data['cost'] ?? null,
                'currency' => $data['currency'] ?? 'ETB',
                'started_at' => $data['started_at'] ?? today(),
                'completed_at' => $data['completed_at'] ?? null,
                'next_due_at' => $data['next_due_at'] ?? null,
                'fault_description' => $data['fault_description'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // An episode logged after the fact (completed on creation) shouldn't
            // pull a working asset out of service.
            if ($log->isOpen()) {
                $unit->update(['status' => InventoryUnitStatus::UnderMaintenance]);
            } else {
                $this->applyCompletion($unit, $log, $data);
            }

            return $log->fresh(['unit.item', 'supplier', 'reportedBy']);
        });
    }

    /**
     * Close a maintenance episode and put the asset back where it belongs.
     *
     * @param  array<string, mixed>  $data
     */
    public function completeMaintenance(InventoryMaintenanceLog $log, array $data, User $performedBy): InventoryMaintenanceLog
    {
        return DB::transaction(function () use ($log, $data, $performedBy) {
            if (! $log->isOpen()) {
                throw ValidationException::withMessages([
                    'completed_at' => 'This maintenance episode is already closed.',
                ]);
            }

            $unit = $this->lockUnit($log->unit_id);

            $log->update([
                'completed_at' => $data['completed_at'] ?? today(),
                'cost' => $data['cost'] ?? $log->cost,
                'supplier_id' => $data['supplier_id'] ?? $log->supplier_id,
                'vendor_name' => $data['vendor_name'] ?? $log->vendor_name,
                'next_due_at' => $data['next_due_at'] ?? $log->next_due_at,
                'downtime_days' => $data['downtime_days'] ?? null,
                'outcome' => $data['outcome'] ?? $log->outcome,
                'notes' => $data['notes'] ?? $log->notes,
            ]);

            $this->applyCompletion($unit, $log->fresh(), $data);

            return $log->fresh(['unit.item', 'supplier', 'reportedBy']);
        });
    }

    /**
     * Register edits that don't move anything: serial number, condition,
     * depreciation policy, warranty, notes.
     *
     * Purchase cost is deliberately editable — a GRN can be recorded before the
     * invoice arrives — but every change is captured by LogsOperationalActivity,
     * because cost drives book value and book value drives write-off approvals.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUnit(InventoryUnit $unit, array $data): InventoryUnit
    {
        if (! $unit->acceptsMovements() && ! empty($data['status'])) {
            throw ValidationException::withMessages([
                'status' => "Asset {$unit->asset_tag} is {$unit->status->label()} — reinstate it through an adjustment, not an edit.",
            ]);
        }

        $unit->update(array_filter([
            'serial_number' => $data['serial_number'] ?? null,
            'condition' => $data['condition'] ?? null,
            'purchase_cost' => $data['purchase_cost'] ?? null,
            'depreciation_method' => $data['depreciation_method'] ?? null,
            'useful_life_months' => $data['useful_life_months'] ?? null,
            'salvage_value' => $data['salvage_value'] ?? null,
            'in_service_on' => $data['in_service_on'] ?? null,
            'warranty_until' => $data['warranty_until'] ?? null,
            'next_maintenance_due_at' => $data['next_maintenance_due_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        return $unit->fresh(['item.category', 'location', 'assignedTo']);
    }

    /**
     * Units an employee still holds — the figure the termination clearance gate
     * reads. Counts open custody records rather than the denormalized pointer,
     * because the custody ledger is the authoritative one.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, InventoryAssetAssignment>
     */
    public function heldBy(Employee $employee)
    {
        return InventoryAssetAssignment::open()
            ->forEmployee($employee->id)
            ->with(['unit.item:id,name_en,code', 'unit:id,item_id,asset_tag,serial_number,condition,purchase_cost'])
            ->orderBy('issued_at')
            ->get();
    }

    // ---- Internals -----------------------------------------------------------

    /**
     * Put a repaired asset back into service. A recurring type schedules its own
     * next visit, so preventive maintenance doesn't depend on someone remembering.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyCompletion(InventoryUnit $unit, InventoryMaintenanceLog $log, array $data): void
    {
        $updates = [
            'last_maintenance_at' => $log->completed_at ?? now(),
            'next_maintenance_due_at' => $log->next_due_at,
        ];

        if (! empty($data['condition'])) {
            $updates['condition'] = InventoryCondition::from($data['condition']);
        }

        // Only reinstate a unit that maintenance itself took out of service —
        // an episode logged against an issued asset leaves custody untouched.
        if ($unit->status === InventoryUnitStatus::UnderMaintenance) {
            $condition = $updates['condition'] ?? $unit->condition;

            $updates['status'] = $condition->isEndOfLife()
                ? InventoryUnitStatus::UnderMaintenance   // beyond repair: hold it for disposal
                : InventoryUnitStatus::InStore;
        }

        $unit->update($updates);
    }

    /** Invariant 3: a unit already out cannot be handed to a second person. */
    private function assertAssignable(InventoryUnit $unit): void
    {
        if ($unit->status !== InventoryUnitStatus::InStore) {
            throw ValidationException::withMessages([
                'unit_id' => "Asset {$unit->asset_tag} is {$unit->status->label()} — it must be returned to store before it can be handed out again.",
            ]);
        }

        if ($unit->openAssignment) {
            throw ValidationException::withMessages([
                'unit_id' => "Asset {$unit->asset_tag} still has an open custody record with "
                    .($unit->openAssignment->employee?->full_name_en ?? 'another employee').'.',
            ]);
        }
    }

    private function lockUnit(int $unitId): InventoryUnit
    {
        $query = InventoryUnit::query()->where('id', $unitId);

        return ($this->driverSupportsLocks() ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    /** Mirrors StockLedger: lockForUpdate is a no-op on SQLite. */
    private function driverSupportsLocks(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }
}
