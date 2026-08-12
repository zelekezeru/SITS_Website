<?php

namespace App\Models;

use App\Enums\InventoryCondition;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One custody episode: an asset handed to an employee and (eventually) handed
 * back. The history, not just a current pointer — which is what makes
 * "who has had this, and what condition did each of them return it in"
 * answerable.
 *
 * Comparing condition_in against condition_out is how damage becomes
 * attributable rather than merely noticed.
 */
class InventoryAssetAssignment extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'unit_id',
        'employee_id',
        'issued_at',
        'due_at',
        'returned_at',
        'condition_out',
        'condition_in',
        'issued_by',
        'received_back_by',
        'purpose',
        'acknowledgement_path',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'date',
        'returned_at' => 'datetime',
        'condition_out' => InventoryCondition::class,
        'condition_in' => InventoryCondition::class,
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receivedBackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_back_by');
    }

    public function isOpen(): bool
    {
        return $this->returned_at === null;
    }

    /** Past its due date and still not back. */
    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_at->diffInDays(now()) : 0;
    }

    public function daysHeld(): int
    {
        return (int) $this->issued_at->diffInDays($this->returned_at ?? now());
    }

    /** Came back in worse shape than it went out. */
    public function cameBackDamaged(): bool
    {
        return $this->condition_in !== null
            && $this->condition_out !== null
            && $this->condition_in->isWorseThan($this->condition_out);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay());
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
