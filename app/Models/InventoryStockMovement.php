<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in the stock ledger — the source of truth for every quantity in the
 * module.
 *
 * Append-only by design (invariant 4): a mistake is corrected with a
 * compensating row carrying a stated reason, never by editing history. The two
 * guards below enforce that at the model level, so no controller, service or
 * console command can quietly rewrite the past.
 *
 * `quantity` is signed — positive adds, negative removes — and
 * InventoryMovementType::direction() is the only authority on the sign.
 */
class InventoryStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'unit_id',
        'batch_id',
        'type',
        'quantity',
        'from_location_id',
        'to_location_id',
        'employee_id',
        'request_id',
        'disposal_id',
        'reference',
        'unit_cost',
        'occurred_at',
        'performed_by',
        'reason',
        'notes',
    ];

    protected $casts = [
        'type' => InventoryMovementType::class,
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // The ledger is immutable. Anything that needs to "change" a movement
        // posts a compensating one, so the trail shows the error and the fix.
        static::updating(function (self $movement) {
            throw new \LogicException(
                'Stock movements are append-only. Post a compensating movement instead of editing #'.$movement->id.'.'
            );
        });

        static::deleting(function (self $movement) {
            throw new \LogicException(
                'Stock movements cannot be deleted. Post a compensating movement instead of removing #'.$movement->id.'.'
            );
        });
    }

    // ---- Relations -----------------------------------------------------------

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    /** The counterparty — who received an issue or handed back a return. */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(InventoryRequest::class, 'request_id');
    }

    public function disposal(): BelongsTo
    {
        return $this->belongsTo(InventoryDisposal::class, 'disposal_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ---- Derived -------------------------------------------------------------

    public function isInward(): bool
    {
        return (float) $this->quantity > 0;
    }

    /** Value of this movement at the cost snapshotted when it was posted. */
    public function value(): ?float
    {
        return $this->unit_cost === null
            ? null
            : round(abs((float) $this->quantity) * (float) $this->unit_cost, 2);
    }

    // ---- Scopes --------------------------------------------------------------

    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('item_id', $itemId);
    }

    /** Movements touching a location in either direction. */
    public function scopeAtLocation(Builder $query, int|array $locationIds): Builder
    {
        $ids = (array) $locationIds;

        return $query->where(fn (Builder $q) => $q
            ->whereIn('to_location_id', $ids)
            ->orWhereIn('from_location_id', $ids));
    }

    public function scopeOutward(Builder $query): Builder
    {
        return $query->where('quantity', '<', 0);
    }

    public function scopeInward(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }
}
