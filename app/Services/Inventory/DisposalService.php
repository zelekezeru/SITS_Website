<?php

namespace App\Services\Inventory;

use App\Enums\InventoryDisposalMethod;
use App\Enums\InventoryDisposalStatus;
use App\Models\InventoryDisposal;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\User;
use App\Support\Inventory\InventoryCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Write-off, sale, donation and scrap — the classic loss vector, so it costs two
 * signatures and never happens in one step.
 *
 * The custodian proposes (StorePermission::MANAGE_ASSETS), a checker decides
 * (StorePermission::APPROVE_DISPOSAL, which the Store Keeper deliberately does
 * not hold), and only then does complete() post the movement that takes the thing
 * off the books. Approval and posting are separate calls on purpose: an approved
 * disposal that hasn't physically happened yet is a real state — the buyer hasn't
 * collected, the scrap merchant hasn't come.
 *
 * @see docs/inventory-management-design.md §3, §5
 */
class DisposalService
{
    public function __construct(private readonly StockLedger $ledger) {}

    /**
     * Raise a disposal proposal. Book value is snapshotted at proposal time
     * rather than recomputed at approval, so the approver signs off the figure
     * they were actually shown.
     *
     * @param  array<string, mixed>  $data
     */
    public function propose(array $data, User $proposedBy): InventoryDisposal
    {
        return DB::transaction(function () use ($data, $proposedBy) {
            $method = InventoryDisposalMethod::from($data['method']);

            $unit = ! empty($data['unit_id']) ? InventoryUnit::findOrFail($data['unit_id']) : null;
            $item = $unit?->item ?? (! empty($data['item_id']) ? InventoryItem::findOrFail($data['item_id']) : null);

            if (! $unit && ! $item) {
                throw ValidationException::withMessages([
                    'item_id' => 'Select either a serialized asset or a catalog item to dispose of.',
                ]);
            }

            if ($unit && ! $unit->acceptsMovements()) {
                throw ValidationException::withMessages([
                    'unit_id' => "Asset {$unit->asset_tag} is already {$unit->status->label()}.",
                ]);
            }

            // A second live proposal against the same asset would let two
            // approvers each post a disposal movement for one physical thing.
            if ($unit && $this->hasLiveProposal($unit)) {
                throw ValidationException::withMessages([
                    'unit_id' => "Asset {$unit->asset_tag} already has a disposal awaiting a decision.",
                ]);
            }

            $quantity = $unit ? 1.0 : (float) ($data['quantity'] ?? 0);

            if (! $unit && $quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Enter the quantity being disposed of.',
                ]);
            }

            if (! $unit && $quantity > $item->onHand($data['location_id'] ?? null) + 1e-9) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot dispose of more than is on hand.',
                ]);
            }

            if ($method->yieldsProceeds() && ! isset($data['proceeds'])) {
                throw ValidationException::withMessages([
                    'proceeds' => 'A sale must record the amount received.',
                ]);
            }

            return InventoryDisposal::create([
                'reference' => InventoryCodeGenerator::disposalReference(),
                'unit_id' => $unit?->id,
                'item_id' => $unit?->item_id ?? $item->id,
                'batch_id' => $unit?->batch_id ?? ($data['batch_id'] ?? null),
                'location_id' => $data['location_id'] ?? $unit?->current_location_id,
                'quantity' => $quantity,
                'method' => $method,
                'status' => InventoryDisposalStatus::Proposed,
                'reason' => $data['reason'],
                'book_value' => $data['book_value'] ?? $this->bookValue($unit, $item, $quantity),
                'proceeds' => $data['proceeds'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'proposed_by' => $proposedBy->id,
                'proposed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ])->fresh(['unit.item', 'item', 'location', 'proposedBy']);
        });
    }

    /**
     * The second signature. Approval alone posts nothing — see complete().
     *
     * @param  array<string, mixed>  $data
     */
    public function approve(InventoryDisposal $disposal, array $data, User $approvedBy): InventoryDisposal
    {
        $this->assertAwaitingDecision($disposal);

        // The checker must be a different person from the proposer: one user
        // holding both permissions would otherwise satisfy maker-checker alone.
        if ($disposal->proposed_by === $approvedBy->id) {
            throw ValidationException::withMessages([
                'approved_by' => 'A disposal cannot be approved by the person who proposed it.',
            ]);
        }

        $disposal->update([
            'status' => InventoryDisposalStatus::Approved,
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
            'proceeds' => $data['proceeds'] ?? $disposal->proceeds,
            'recipient' => $data['recipient'] ?? $disposal->recipient,
            'notes' => $data['notes'] ?? $disposal->notes,
        ]);

        return $disposal->fresh(['unit.item', 'item', 'approvedBy']);
    }

    public function reject(InventoryDisposal $disposal, string $reason, User $rejectedBy): InventoryDisposal
    {
        $this->assertAwaitingDecision($disposal);

        $disposal->update([
            'status' => InventoryDisposalStatus::Rejected,
            'approved_by' => $rejectedBy->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $disposal->fresh(['unit.item', 'item', 'approvedBy']);
    }

    /**
     * Post the approved disposal to the ledger — the point of no return.
     * Idempotent by status: a completed disposal is closed and cannot re-post.
     */
    public function complete(InventoryDisposal $disposal, User $performedBy): InventoryDisposal
    {
        return DB::transaction(function () use ($disposal, $performedBy) {
            if (! $disposal->status->isPostable()) {
                throw ValidationException::withMessages([
                    'status' => $disposal->status->isClosed()
                        ? "Disposal {$disposal->reference} is already {$disposal->status->label()}."
                        : "Disposal {$disposal->reference} must be approved before it can be posted.",
                ]);
            }

            $this->ledger->disposeOf($disposal, $performedBy);

            $disposal->update([
                'status' => InventoryDisposalStatus::Completed,
                'completed_at' => now(),
            ]);

            return $disposal->fresh(['unit.item', 'item', 'movements']);
        });
    }

    /** Withdraw a proposal that hasn't been decided on. */
    public function cancel(InventoryDisposal $disposal, User $user): InventoryDisposal
    {
        if ($disposal->status->isClosed() || $disposal->status->isPostable()) {
            throw ValidationException::withMessages([
                'status' => "Disposal {$disposal->reference} can no longer be cancelled.",
            ]);
        }

        $disposal->update(['status' => InventoryDisposalStatus::Cancelled]);

        return $disposal->fresh();
    }

    // ---- Internals -----------------------------------------------------------

    private function assertAwaitingDecision(InventoryDisposal $disposal): void
    {
        if (! $disposal->status->awaitsApproval()) {
            throw ValidationException::withMessages([
                'status' => "Disposal {$disposal->reference} is {$disposal->status->label()} and is no longer awaiting a decision.",
            ]);
        }
    }

    private function hasLiveProposal(InventoryUnit $unit): bool
    {
        return InventoryDisposal::where('unit_id', $unit->id)
            ->whereIn('status', [InventoryDisposalStatus::Proposed, InventoryDisposalStatus::Approved])
            ->exists();
    }

    /**
     * What the books say the thing is worth right now: an asset's depreciated
     * book value, or a consumable's average cost times quantity.
     */
    private function bookValue(?InventoryUnit $unit, ?InventoryItem $item, float $quantity): ?float
    {
        if ($unit) {
            return $unit->bookValue();
        }

        $cost = $item?->averageUnitCost();

        return $cost === null ? null : round($cost * $quantity, 2);
    }
}
