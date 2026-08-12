<?php

namespace App\Models;

use App\Enums\InventoryMaintenanceType;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A service episode on one asset: what went wrong or was due, who fixed it, what
 * it cost, and when the next one falls due.
 *
 * Accumulated cost against book value is what turns "repair it again" into a
 * decision rather than a reflex.
 */
class InventoryMaintenanceLog extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'unit_id',
        'type',
        'reported_by',
        'supplier_id',
        'vendor_name',
        'cost',
        'currency',
        'started_at',
        'completed_at',
        'next_due_at',
        'downtime_days',
        'fault_description',
        'outcome',
        'notes',
    ];

    protected $casts = [
        'type' => InventoryMaintenanceType::class,
        'cost' => 'decimal:2',
        'started_at' => 'date',
        'completed_at' => 'date',
        'next_due_at' => 'date',
        'downtime_days' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'supplier_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null;
    }

    /** Named supplier if there is one, else the one-off vendor. */
    public function servicedBy(): ?string
    {
        return $this->supplier?->name ?? $this->vendor_name;
    }

    /** Days out of service — measured if closed, running if still open. */
    public function effectiveDowntimeDays(): int
    {
        if ($this->downtime_days !== null) {
            return $this->downtime_days;
        }

        return (int) $this->started_at->diffInDays($this->completed_at ?? now());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeUnplanned(Builder $query): Builder
    {
        return $query->where('type', InventoryMaintenanceType::Repair);
    }

    public function scopeDueWithin(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('next_due_at')
            ->whereBetween('next_due_at', [now()->startOfDay(), now()->addDays($days)->endOfDay()]);
    }
}
