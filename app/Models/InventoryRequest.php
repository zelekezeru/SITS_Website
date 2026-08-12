<?php

namespace App\Models;

use App\Enums\InventoryRequestStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staff requisition — the maker-checker gate in front of the store.
 *
 * The custodian of the goods cannot authorise their own release, so approval
 * belongs to a department head, Operations, the Dean or the VP; only then may
 * the store keeper issue against it.
 */
class InventoryRequest extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'request_number',
        'requested_by_employee_id',
        'department_id',
        'status',
        'purpose',
        'needed_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'issued_by',
        'fulfilled_at',
        'notes',
    ];

    protected $casts = [
        'status' => InventoryRequestStatus::class,
        'needed_by' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryRequestLine::class, 'request_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'request_id');
    }

    // ---- Derived -------------------------------------------------------------

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isIssuable(): bool
    {
        return $this->status->isIssuable();
    }

    /** Everything approved has been issued — the trigger to close the request. */
    public function isFullyIssued(): bool
    {
        $lines = $this->lines;

        return $lines->isNotEmpty() && $lines->every(
            fn (InventoryRequestLine $line) => $line->isFullyIssued()
        );
    }

    /** Some but not all of what was approved has gone out. */
    public function isPartiallyIssued(): bool
    {
        $lines = $this->lines;

        return $lines->contains(fn (InventoryRequestLine $l) => (float) $l->quantity_issued > 0)
            && ! $this->isFullyIssued();
    }

    /** Status the ledger says it should be in, after an issue is posted. */
    public function derivedStatus(): InventoryRequestStatus
    {
        if ($this->status->isClosed() || ! $this->status->isIssuable()) {
            return $this->status;
        }

        return match (true) {
            $this->isFullyIssued() => InventoryRequestStatus::Fulfilled,
            $this->isPartiallyIssued() => InventoryRequestStatus::PartiallyFulfilled,
            default => $this->status,
        };
    }

    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->where('status', InventoryRequestStatus::Submitted);
    }

    public function scopeIssuable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InventoryRequestStatus::Approved,
            InventoryRequestStatus::PartiallyFulfilled,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            InventoryRequestStatus::Fulfilled,
            InventoryRequestStatus::Rejected,
            InventoryRequestStatus::Cancelled,
        ]);
    }
}
