<?php

namespace App\Models;

use App\Enums\InventoryStocktakeStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A physical count session.
 *
 * The Review → Posted step is the control that matters: counting is the store
 * keeper's job, but turning a variance into a stock adjustment — the move that
 * would quietly erase shrinkage — needs StorePermission::ADJUST, which the
 * custodian does not hold.
 */
class InventoryStocktake extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'reference',
        'location_id',
        'category_id',
        'status',
        'scope',
        'started_by',
        'started_at',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'status' => InventoryStocktakeStatus::class,
        'started_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryStocktakeLine::class, 'stocktake_id');
    }

    // ---- Derived -------------------------------------------------------------

    public function isPostable(): bool
    {
        return $this->status->isPostable();
    }

    /** Invariant 6: posting is idempotent, so a posted session is frozen. */
    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function countedLinesCount(): int
    {
        return $this->lines()->whereNotNull('counted_quantity')->count();
    }

    public function pendingLinesCount(): int
    {
        return $this->lines()->whereNull('counted_quantity')->count();
    }

    /** Lines where what was counted differs from what the system believed. */
    public function varianceLinesCount(): int
    {
        return $this->lines()->whereNotNull('variance')->where('variance', '!=', 0)->count();
    }

    /** Net variance across the session — the headline figure on the report. */
    public function netVariance(): float
    {
        return round((float) $this->lines()->sum('variance'), 3);
    }

    /** Total movement regardless of sign; a net of zero can still hide churn. */
    public function absoluteVariance(): float
    {
        return round((float) $this->lines()
            ->whereNotNull('variance')
            ->get()
            ->sum(fn (InventoryStocktakeLine $l) => abs((float) $l->variance)), 3);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            InventoryStocktakeStatus::Posted,
            InventoryStocktakeStatus::Cancelled,
        ]);
    }

    public function scopeAwaitingPosting(Builder $query): Builder
    {
        return $query->where('status', InventoryStocktakeStatus::Review);
    }
}
