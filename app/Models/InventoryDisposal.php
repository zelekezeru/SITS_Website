<?php

namespace App\Models;

use App\Enums\InventoryDisposalMethod;
use App\Enums\InventoryDisposalStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A proposal to remove something from the books — one serialized unit, or a
 * quantity of a consumable (an expired lot).
 *
 * Never a single step: the custodian proposes, a checker holding
 * StorePermission::APPROVE_DISPOSAL decides, and only then is the movement
 * posted. Writing an asset off is the classic loss vector, so it costs two
 * signatures.
 */
class InventoryDisposal extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'reference',
        'unit_id',
        'item_id',
        'batch_id',
        'location_id',
        'quantity',
        'method',
        'status',
        'reason',
        'book_value',
        'proceeds',
        'recipient',
        'proposed_by',
        'proposed_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'method' => InventoryDisposalMethod::class,
        'status' => InventoryDisposalStatus::class,
        'quantity' => 'decimal:3',
        'book_value' => 'decimal:2',
        'proceeds' => 'decimal:2',
        'proposed_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'disposal_id');
    }

    /** A serialized asset, as opposed to a consumable quantity. */
    public function isUnitDisposal(): bool
    {
        return $this->unit_id !== null;
    }

    /** Net effect on the books: proceeds recovered against value written off. */
    public function netLoss(): float
    {
        return round((float) ($this->book_value ?? 0) - (float) ($this->proceeds ?? 0), 2);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InventoryDisposalStatus::Proposed);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', InventoryDisposalStatus::Approved);
    }

    /** Losses an auditor will ask about. */
    public function scopeLossEvents(Builder $query): Builder
    {
        return $query->whereIn('method', [
            InventoryDisposalMethod::Lost,
            InventoryDisposalMethod::WrittenOff,
        ]);
    }
}
