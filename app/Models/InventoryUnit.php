<?php

namespace App\Models;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryCondition;
use App\Enums\InventoryUnitStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One physical serialized asset — *that* laptop, with its own tag, condition,
 * location and custody history.
 *
 * `assigned_to_employee_id` is a denormalized pointer to the open assignment,
 * maintained by the service layer for cheap list queries; the authoritative
 * custody record is InventoryAssetAssignment.
 */
class InventoryUnit extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'item_id',
        'batch_id',
        'asset_tag',
        'serial_number',
        'status',
        'condition',
        'current_location_id',
        'assigned_to_employee_id',
        'assigned_at',
        'purchase_cost',
        'depreciation_method',
        'useful_life_months',
        'salvage_value',
        'in_service_on',
        'warranty_until',
        'last_maintenance_at',
        'next_maintenance_due_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => InventoryUnitStatus::class,
        'condition' => InventoryCondition::class,
        'depreciation_method' => DepreciationMethod::class,
        'purchase_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_months' => 'integer',
        'assigned_at' => 'datetime',
        'in_service_on' => 'date',
        'warranty_until' => 'date',
        'last_maintenance_at' => 'datetime',
        'next_maintenance_due_at' => 'date',
    ];

    // ---- Relations -----------------------------------------------------------

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
        return $this->belongsTo(InventoryLocation::class, 'current_location_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to_employee_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InventoryAssetAssignment::class, 'unit_id')->orderByDesc('issued_at');
    }

    /** The open custody record, if the unit is out with someone. */
    public function openAssignment(): HasOne
    {
        return $this->hasOne(InventoryAssetAssignment::class, 'unit_id')
            ->whereNull('returned_at')
            ->latestOfMany('issued_at');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(InventoryMaintenanceLog::class, 'unit_id')->orderByDesc('started_at');
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(InventoryDisposal::class, 'unit_id')->latestOfMany();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'unit_id')->orderByDesc('occurred_at');
    }

    // ---- Derived -------------------------------------------------------------

    public function isAvailable(): bool
    {
        return $this->status->isAvailable();
    }

    /** Invariant 7: a disposed or lost unit accepts no further movements. */
    public function acceptsMovements(): bool
    {
        return ! $this->status->isTerminal();
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_until !== null && $this->warranty_until->isFuture();
    }

    /** Overdue against the due date on its open assignment. */
    public function isOverdue(): bool
    {
        $assignment = $this->openAssignment;

        return $assignment !== null
            && $assignment->due_at !== null
            && $assignment->due_at->isPast();
    }

    public function isMaintenanceDue(): bool
    {
        return $this->next_maintenance_due_at !== null
            && ! $this->next_maintenance_due_at->isFuture();
    }

    public function monthsInService(): int
    {
        $start = $this->in_service_on ?? $this->batch?->purchase_date ?? $this->created_at;

        return $start ? max(0, (int) $start->diffInMonths(now())) : 0;
    }

    /** Policy from the unit, else the item, else the item's category. */
    public function effectiveDepreciationMethod(): DepreciationMethod
    {
        if ($this->depreciation_method !== null && $this->depreciation_method !== DepreciationMethod::None) {
            return $this->depreciation_method;
        }

        return $this->item?->effectiveDepreciationMethod() ?? DepreciationMethod::None;
    }

    public function accumulatedDepreciation(): float
    {
        $cost = (float) ($this->purchase_cost ?? 0);
        $life = $this->useful_life_months ?? $this->item?->effectiveUsefulLifeMonths() ?? 0;

        if ($cost <= 0 || $life <= 0) {
            return 0.0;
        }

        return $this->effectiveDepreciationMethod()
            ->accumulated($cost, (float) $this->salvage_value, $life, $this->monthsInService());
    }

    /** Cost less accumulated depreciation — the asset register's book value. */
    public function bookValue(): float
    {
        $cost = (float) ($this->purchase_cost ?? 0);

        return round(max($cost - $this->accumulatedDepreciation(), (float) $this->salvage_value), 2);
    }

    public function totalMaintenanceCost(): float
    {
        return round((float) $this->maintenanceLogs()->getQuery()->reorder()->sum('cost'), 2);
    }

    // ---- Scopes --------------------------------------------------------------

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', InventoryUnitStatus::InStore);
    }

    public function scopeOut(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InventoryUnitStatus::Issued,
            InventoryUnitStatus::Deployed,
            InventoryUnitStatus::UnderMaintenance,
        ]);
    }

    public function scopeInRegister(Builder $query): Builder
    {
        return $query->whereNotIn('status', [InventoryUnitStatus::Disposed]);
    }

    public function scopeHeldBy(Builder $query, int $employeeId): Builder
    {
        return $query->where('assigned_to_employee_id', $employeeId)->whereNull('deleted_at');
    }

    public function scopeMaintenanceDue(Builder $query): Builder
    {
        return $query->whereNotNull('next_maintenance_due_at')
            ->where('next_maintenance_due_at', '<=', now()->endOfDay())
            ->inRegister();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('asset_tag', 'like', $like)
                ->orWhere('serial_number', 'like', $like)
                ->orWhereHas('item', fn (Builder $i) => $i->where('name_en', 'like', $like));
        });
    }
}
