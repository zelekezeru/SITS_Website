<?php

namespace App\Services\Inventory;

use App\Enums\InventoryCondition;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryRequestStatus;
use App\Enums\InventoryUnitStatus;
use App\Models\InventoryAssetAssignment;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequest;
use App\Models\InventoryRequestLine;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Models\User;
use App\Support\Inventory\InventoryCodeGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * Issue inventory to an employee or department, optionally against an approved InventoryRequest.
     *
     * @param  array<string, mixed>  $data
     */
    public function issue(array $data, User $performedBy): InventoryStockMovement
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $item = $this->lockForUpdate(InventoryItem::query()->where('id', $data['item_id']))->firstOrFail();
            $quantity = (float) ($data['quantity'] ?? 1);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            $unit = null;
            if ($item->isSerialized()) {
                if ($quantity !== 1.0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Serialized assets must be issued one unit at a time.',
                    ]);
                }

                if (empty($data['unit_id'])) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Please select a specific asset unit to issue.',
                    ]);
                }

                $unit = $this->lockForUpdate(InventoryUnit::query()->where('id', $data['unit_id']))->firstOrFail();

                if ($unit->status !== InventoryUnitStatus::InStore) {
                    throw ValidationException::withMessages([
                        'unit_id' => "Asset {$unit->asset_tag} is currently {$unit->status->label()} and cannot be issued.",
                    ]);
                }
            }

            $requestLine = null;
            $request = null;
            if (! empty($data['request_line_id'])) {
                $requestLine = InventoryRequestLine::where('id', $data['request_line_id'])->first();
                if ($requestLine) {
                    $request = $requestLine->request;
                    $approved = (float) ($requestLine->quantity_approved ?? 0);
                    $issued = (float) $requestLine->quantity_issued;
                    $outstanding = max($approved - $issued, 0);

                    if ($quantity > $outstanding + 1e-9) {
                        throw ValidationException::withMessages([
                            'quantity' => "Cannot issue {$quantity}: only {$outstanding} outstanding on this approved requisition line.",
                        ]);
                    }
                }
            } elseif (! empty($data['request_id'])) {
                $request = InventoryRequest::find($data['request_id']);
            }

            $reference = $data['reference'] ?? InventoryCodeGenerator::issueVoucher();
            $fromLocationId = $data['from_location_id'] ?? ($unit?->current_location_id ?? null);
            $employeeId = $data['employee_id'] ?? ($request?->requested_by_employee_id ?? null);

            $movement = $this->postMovement(
                item: $item,
                batch: $unit?->batch ?? null,
                type: InventoryMovementType::Issue,
                quantity: $quantity,
                fromLocationId: $fromLocationId,
                toLocationId: null,
                unitCost: $unit?->purchase_cost,
                performedBy: $performedBy,
                occurredAt: $data['occurred_at'] ?? now(),
                unit: $unit,
                reference: $reference,
                reason: $data['reason'] ?? null,
                employeeId: $employeeId,
                requestId: $request?->id,
                notes: $data['notes'] ?? null,
            );

            if ($unit) {
                $unit->update([
                    'status' => InventoryUnitStatus::Issued,
                    'assigned_to_employee_id' => $employeeId,
                    'assigned_at' => $data['occurred_at'] ?? now(),
                    'current_location_id' => null,
                ]);

                if ($employeeId) {
                    InventoryAssetAssignment::create([
                        'unit_id' => $unit->id,
                        'employee_id' => $employeeId,
                        'issued_by' => $performedBy->id,
                        'issued_at' => $data['occurred_at'] ?? now(),
                        'expected_return_at' => $data['expected_return_at'] ?? null,
                        'condition_on_issue' => $unit->condition,
                        'notes' => $data['notes'] ?? null,
                    ]);
                }
            }

            if ($requestLine) {
                $requestLine->increment('quantity_issued', $quantity);
                $request = $request->fresh(['lines']);
                $newStatus = $request->derivedStatus();
                $updates = ['status' => $newStatus, 'issued_by' => $performedBy->id];
                if ($newStatus === InventoryRequestStatus::Fulfilled) {
                    $updates['fulfilled_at'] = now();
                }
                $request->update($updates);
            }

            return $movement;
        });
    }

    /**
     * Return previously issued goods / assets back to store.
     *
     * @param  array<string, mixed>  $data
     */
    public function returnStock(array $data, User $performedBy): InventoryStockMovement
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $item = $this->lockForUpdate(InventoryItem::query()->where('id', $data['item_id']))->firstOrFail();
            $quantity = (float) ($data['quantity'] ?? 1);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Return quantity must be greater than zero.',
                ]);
            }

            $unit = null;
            if ($item->isSerialized()) {
                if (empty($data['unit_id'])) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Please select the asset unit being returned.',
                    ]);
                }

                $unit = $this->lockForUpdate(InventoryUnit::query()->where('id', $data['unit_id']))->firstOrFail();
                $quantity = 1.0;
            }

            $toLocationId = $data['to_location_id'] ?? null;
            $employeeId = $data['employee_id'] ?? ($unit?->assigned_to_employee_id ?? null);
            $condition = isset($data['condition']) ? InventoryCondition::tryFrom($data['condition']) : ($unit?->condition ?? InventoryCondition::Good);

            $movement = $this->postMovement(
                item: $item,
                batch: $unit?->batch ?? null,
                type: InventoryMovementType::Return,
                quantity: $quantity,
                fromLocationId: null,
                toLocationId: $toLocationId,
                unitCost: $unit?->purchase_cost,
                performedBy: $performedBy,
                occurredAt: $data['occurred_at'] ?? now(),
                unit: $unit,
                reference: $data['reference'] ?? null,
                reason: $data['reason'] ?? 'Return to store',
                employeeId: $employeeId,
                notes: $data['notes'] ?? null,
            );

            if ($unit) {
                $openAssignment = $unit->openAssignment;
                if ($openAssignment) {
                    $openAssignment->update([
                        'returned_at' => $data['occurred_at'] ?? now(),
                        'condition_on_return' => $condition ?? $unit->condition,
                    ]);
                }

                $unit->update([
                    'status' => InventoryUnitStatus::InStore,
                    'condition' => $condition ?? $unit->condition,
                    'assigned_to_employee_id' => null,
                    'assigned_at' => null,
                    'current_location_id' => $toLocationId,
                ]);
            }

            return $movement;
        });
    }

    /**
     * Move stock / asset from one location to another.
     * Posts paired TransferOut and TransferIn rows.
     *
     * @param  array<string, mixed>  $data
     * @return array{out: InventoryStockMovement, in: InventoryStockMovement}
     */
    public function transfer(array $data, User $performedBy): array
    {
        return DB::transaction(function () use ($data, $performedBy) {
            $item = $this->lockForUpdate(InventoryItem::query()->where('id', $data['item_id']))->firstOrFail();
            $fromLocationId = (int) $data['from_location_id'];
            $toLocationId = (int) $data['to_location_id'];

            if ($fromLocationId === $toLocationId) {
                throw ValidationException::withMessages([
                    'to_location_id' => 'Destination location cannot be the same as source location.',
                ]);
            }

            $quantity = (float) ($data['quantity'] ?? 1);
            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Transfer quantity must be greater than zero.',
                ]);
            }

            $unit = null;
            if ($item->isSerialized()) {
                if (empty($data['unit_id'])) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Please select the asset unit being transferred.',
                    ]);
                }

                $unit = $this->lockForUpdate(InventoryUnit::query()->where('id', $data['unit_id']))->firstOrFail();
                $quantity = 1.0;

                if ($unit->current_location_id !== null && $unit->current_location_id !== $fromLocationId) {
                    throw ValidationException::withMessages([
                        'from_location_id' => 'Asset unit is currently in '.$unit->location?->name.', not the selected source location.',
                    ]);
                }
            }

            $reference = $data['reference'] ?? 'TRF-'.now()->year.'-'.strtoupper(Str::random(6));

            $outMovement = $this->postMovement(
                item: $item,
                batch: $unit?->batch ?? null,
                type: InventoryMovementType::TransferOut,
                quantity: $quantity,
                fromLocationId: $fromLocationId,
                toLocationId: null,
                unitCost: $unit?->purchase_cost,
                performedBy: $performedBy,
                occurredAt: $data['occurred_at'] ?? now(),
                unit: $unit,
                reference: $reference,
                reason: $data['reason'] ?? null,
                notes: $data['notes'] ?? null,
            );

            $inMovement = $this->postMovement(
                item: $item,
                batch: $unit?->batch ?? null,
                type: InventoryMovementType::TransferIn,
                quantity: $quantity,
                fromLocationId: null,
                toLocationId: $toLocationId,
                unitCost: $unit?->purchase_cost,
                performedBy: $performedBy,
                occurredAt: $data['occurred_at'] ?? now(),
                unit: $unit,
                reference: $reference,
                reason: $data['reason'] ?? null,
                notes: $data['notes'] ?? null,
            );

            if ($unit) {
                $unit->update([
                    'current_location_id' => $toLocationId,
                ]);
            }

            return ['out' => $outMovement, 'in' => $inMovement];
        });
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
        ?int $employeeId = null,
        ?int $requestId = null,
        ?string $notes = null,
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
            'employee_id' => $employeeId,
            'request_id' => $requestId,
            'reference' => $reference,
            'unit_cost' => $unitCost,
            'occurred_at' => $occurredAt ?? now(),
            'performed_by' => $performedBy->id,
            'reason' => $reason,
            'notes' => $notes,
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
