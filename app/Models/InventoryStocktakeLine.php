<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One counted line in a stocktake.
 *
 * `system_quantity` is snapshotted when the session opens, not read live at
 * posting time — otherwise a movement posted mid-count would silently change
 * what the variance is measured against, and the count would appear to agree
 * with a figure nobody actually counted.
 */
class InventoryStocktakeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stocktake_id',
        'item_id',
        'unit_id',
        'system_quantity',
        'counted_quantity',
        'variance',
        'variance_reason',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'variance' => 'decimal:3',
        'counted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Variance is never entered by hand — it is counted minus system, kept
        // in step with whatever the counter last typed.
        static::saving(function (self $line) {
            $line->variance = $line->counted_quantity === null
                ? null
                : round((float) $line->counted_quantity - (float) $line->system_quantity, 3);
        });
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(InventoryStocktake::class, 'stocktake_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function isCounted(): bool
    {
        return $this->counted_quantity !== null;
    }

    public function hasVariance(): bool
    {
        return $this->variance !== null && abs((float) $this->variance) > 1e-9;
    }

    /** More on the shelf than the books said. */
    public function isSurplus(): bool
    {
        return (float) $this->variance > 0;
    }

    /** Less on the shelf than the books said — the shrinkage case. */
    public function isShortage(): bool
    {
        return (float) $this->variance < 0;
    }

    public function scopeWithVariance(Builder $query): Builder
    {
        return $query->whereNotNull('variance')->where('variance', '!=', 0);
    }

    public function scopeUncounted(Builder $query): Builder
    {
        return $query->whereNull('counted_quantity');
    }
}
