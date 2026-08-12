<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One item on a requisition.
 *
 * Three quantity columns rather than one — requested, approved, issued — so
 * partial approval and partial fulfilment are first-class states instead of a
 * note in a comment field. Invariant 5 holds across them:
 * issued ≤ approved ≤ requested.
 */
class InventoryRequestLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'item_id',
        'unit_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_issued',
        'note',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_approved' => 'decimal:3',
        'quantity_issued' => 'decimal:3',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(InventoryRequest::class, 'request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /** Set when a specific asset was named rather than "any one of these". */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    /** What the store still owes on this line. */
    public function outstanding(): float
    {
        $approved = (float) ($this->quantity_approved ?? 0);

        return round(max($approved - (float) $this->quantity_issued, 0), 3);
    }

    public function isFullyIssued(): bool
    {
        // A line approved for nothing is settled — there is nothing left to give.
        return $this->outstanding() <= 0;
    }

    /** Approved for less than was asked for. */
    public function wasTrimmed(): bool
    {
        return $this->quantity_approved !== null
            && (float) $this->quantity_approved < (float) $this->quantity_requested;
    }

    /** Invariant 5, checkable before a write. */
    public function satisfiesQuantityInvariant(): bool
    {
        $requested = (float) $this->quantity_requested;
        $approved = (float) ($this->quantity_approved ?? 0);
        $issued = (float) $this->quantity_issued;

        return $issued <= $approved + 1e-9 && $approved <= $requested + 1e-9;
    }
}
