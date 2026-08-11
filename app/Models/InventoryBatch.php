<?php

namespace App\Models;

use App\Enums\InventoryCondition;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One goods-received event — a GRN. This is where every purchase fact lives:
 * quantity purchased, unit cost, purchase date, optional production date,
 * expiry, supplier, who physically received it and which account registered it.
 *
 * Modelling these on the receipt rather than the item is what makes FIFO
 * costing, per-lot expiry and supplier performance possible at all.
 */
class InventoryBatch extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'grn_number',
        'item_id',
        'supplier_id',
        'location_id',
        'quantity_received',
        'unit_cost',
        'currency',
        'total_cost',
        'purchase_date',
        'production_date',
        'expiry_date',
        'warranty_until',
        'invoice_number',
        'purchase_order_number',
        'delivery_note_number',
        'condition_on_arrival',
        'received_by_employee_id',
        'registered_by',
        'notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'purchase_date' => 'date',
        'production_date' => 'date',
        'expiry_date' => 'date',
        'warranty_until' => 'date',
        'condition_on_arrival' => InventoryCondition::class,
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'supplier_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    /** The storekeeper who physically took delivery. */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by_employee_id');
    }

    /** The account that entered the record — deliberately a separate person. */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function units(): HasMany
    {
        return $this->hasMany(InventoryUnit::class, 'batch_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'batch_id');
    }

    /** Quantity from this lot still on hand — FIFO consumption draws on this. */
    public function remainingQuantity(): float
    {
        return round((float) $this->movements()->sum('quantity'), 3);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_until !== null && $this->warranty_until->isFuture();
    }

    /** Days until expiry; negative once past, null when not tracked. */
    public function daysToExpiry(): ?int
    {
        return $this->expiry_date === null
            ? null
            : now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /** Lots expiring within $days — the expiry-watch report. */
    public function scopeExpiringWithin(Builder $query, int $days = 90): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->startOfDay(), now()->addDays($days)->endOfDay()]);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()->startOfDay());
    }
}
