<?php

namespace App\Models;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryItemStatus;
use App\Enums\InventoryTrackingMode;
use App\Enums\InventoryUnitStatus;
use App\Enums\UnitOfMeasure;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A catalog entry — the *definition* of a thing the Seminary owns ("Dell Latitude
 * 5420", "A4 Paper 80gsm"), not a physical instance of it.
 *
 * Quantity is never stored on this model. `onHand()` sums the movement ledger,
 * the same way EmployeeLoan::balance sums its payment ledger, so a receipt, an
 * issue, a return, a transfer, a variance and a write-off all reconcile to one
 * number that cannot drift from its own history.
 *
 * Purchase facts (date, supplier, cost, who received it) live on InventoryBatch,
 * because an item bought three times has three of each.
 *
 * @see docs/inventory-management-design.md §1
 */
class InventoryItem extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'code',
        'category_id',
        'name_en',
        'name_am',
        'description',
        'tracking_mode',
        'unit_of_measure',
        'status',
        'brand',
        'model',
        'specification',
        'reorder_level',
        'reorder_quantity',
        'standard_unit_cost',
        'tracks_expiry',
        'depreciation_method',
        'useful_life_months',
        'primary_image_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'tracking_mode' => InventoryTrackingMode::class,
        'unit_of_measure' => UnitOfMeasure::class,
        'status' => InventoryItemStatus::class,
        'depreciation_method' => DepreciationMethod::class,
        'reorder_level' => 'decimal:3',
        'reorder_quantity' => 'decimal:3',
        'standard_unit_cost' => 'decimal:2',
        'useful_life_months' => 'integer',
        'tracks_expiry' => 'boolean',
    ];

    // ---- Relations -----------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'item_id')->orderByDesc('purchase_date');
    }

    public function units(): HasMany
    {
        return $this->hasMany(InventoryUnit::class, 'item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'item_id')->orderByDesc('occurred_at');
    }

    public function requestLines(): HasMany
    {
        return $this->hasMany(InventoryRequestLine::class, 'item_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Photos, invoices, warranties and manuals, distinguished by `category`. */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function images(): MorphMany
    {
        return $this->documents()->where('category', 'image');
    }

    /**
     * The list thumbnail. Not a real relation constraint — `primary_image_id`
     * carries no FK, since a deleted document should leave a harmless dangling
     * id rather than cascade into the catalog.
     */
    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'primary_image_id');
    }

    // ---- Derived quantities --------------------------------------------------

    /**
     * Quantity on hand: Σ signed ledger movements. Optionally scoped to one
     * location subtree, which is how "what's on Shelf B" is answered.
     */
    public function onHand(?int $locationId = null): float
    {
        $query = $this->movements()->getQuery()->reorder();

        if ($locationId !== null) {
            $ids = InventoryLocation::find($locationId)?->descendantIds() ?? [$locationId];

            // A movement counts against a location by where it landed (to_) or
            // left from (from_); summing the signed quantity over both gives the
            // net for that location without special-casing transfers.
            return round((float) $query->where(function (Builder $q) use ($ids) {
                $q->whereIn('to_location_id', $ids)->orWhereIn('from_location_id', $ids);
            })->sum('quantity'), 3);
        }

        return round((float) $query->sum('quantity'), 3);
    }

    /** True when stock has fallen to or below the reorder level. */
    public function needsReorder(): bool
    {
        return $this->status->isReorderable()
            && (float) $this->reorder_level > 0
            && $this->onHand() <= (float) $this->reorder_level;
    }

    /** Serialized assets currently out of the store (issued, deployed, in repair). */
    public function unitsOutCount(): int
    {
        return $this->units()
            ->whereIn('status', array_values(array_filter(
                array_column(InventoryUnitStatus::cases(), 'value'),
                fn (string $s) => InventoryUnitStatus::from($s)->isOut()
            )))
            ->count();
    }

    /**
     * Weighted-average unit cost from receipts, falling back to the planning
     * figure. FIFO lot-by-lot valuation arrives with the reports in Phase 5.
     */
    public function averageUnitCost(): ?float
    {
        $received = (float) $this->batches()->getQuery()->reorder()->sum('quantity_received');

        if ($received <= 0) {
            return $this->standard_unit_cost !== null ? (float) $this->standard_unit_cost : null;
        }

        $spend = (float) $this->batches()->getQuery()->reorder()->sum('total_cost');

        return $spend > 0
            ? round($spend / $received, 2)
            : ($this->standard_unit_cost !== null ? (float) $this->standard_unit_cost : null);
    }

    /** Depreciation policy, inherited from the category when unset. */
    public function effectiveDepreciationMethod(): DepreciationMethod
    {
        return $this->depreciation_method
            ?? $this->category?->default_depreciation_method
            ?? DepreciationMethod::None;
    }

    public function effectiveUsefulLifeMonths(): ?int
    {
        return $this->useful_life_months ?? $this->category?->default_useful_life_months;
    }

    public function isSerialized(): bool
    {
        return $this->tracking_mode->isSerialized();
    }

    // ---- Scopes --------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', InventoryItemStatus::Active);
    }

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('status', InventoryItemStatus::Active);
    }

    public function scopeConsumables(Builder $query): Builder
    {
        return $query->where('tracking_mode', InventoryTrackingMode::Consumable);
    }

    public function scopeAssets(Builder $query): Builder
    {
        return $query->where('tracking_mode', InventoryTrackingMode::Asset);
    }

    /**
     * Items at or below their reorder level, resolved in SQL so the dashboard
     * doesn't load the whole catalog to find a handful of alerts.
     */
    public function scopeNeedingReorder(Builder $query): Builder
    {
        return $query->active()
            ->where('reorder_level', '>', 0)
            ->whereRaw(
                '(select coalesce(sum(quantity), 0) from inventory_stock_movements '
                .'where inventory_stock_movements.item_id = inventory_items.id) <= inventory_items.reorder_level'
            );
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name_en', 'like', $like)
                ->orWhere('name_am', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhere('model', 'like', $like);
        });
    }
}
