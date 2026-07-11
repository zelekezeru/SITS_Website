<?php

namespace App\Models;

use App\Enums\EmployeeLoanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An employee salary loan repaid at a fixed monthly amount via payroll.
 *
 * The outstanding balance is always derived from the payment ledger
 * (principal − Σ payments), never stored, so extra/lump-sum payments and auto
 * payroll deductions all reconcile to the same source of truth.
 */
class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id',
        'reference',
        'principal_amount',
        'monthly_amount',
        'duration_months',
        'start_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'monthly_amount'   => 'decimal:2',
        'duration_months'  => 'integer',
        'start_date'       => 'date',
        'status'           => EmployeeLoanStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeeLoanPayment::class)->latest('paid_on');
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('status', EmployeeLoanStatus::Active);
    }

    // ---- Derived amounts -----------------------------------------------------

    /** Total repaid so far (uses an eager withSum when present, else queries). */
    public function getAmountPaidAttribute(): float
    {
        if (array_key_exists('payments_sum_amount', $this->attributes)) {
            return (float) $this->attributes['payments_sum_amount'];
        }

        return (float) $this->payments()->sum('amount');
    }

    /** Outstanding balance, never negative. */
    public function getBalanceAttribute(): float
    {
        return round(max((float) $this->principal_amount - $this->amount_paid, 0), 2);
    }

    /** Whole months still owed at the fixed monthly amount. */
    public function getMonthsRemainingAttribute(): int
    {
        $monthly = (float) $this->monthly_amount;

        return $monthly > 0 ? (int) ceil($this->balance / $monthly) : 0;
    }

    /** Months settled (rises faster than the calendar when extra is paid). */
    public function getMonthsPaidAttribute(): int
    {
        return max((int) $this->duration_months - $this->months_remaining, 0);
    }

    public function getProgressPercentAttribute(): float
    {
        $principal = (float) $this->principal_amount;

        return $principal > 0 ? round($this->amount_paid / $principal * 100, 1) : 100.0;
    }

    public function getIsSettledAttribute(): bool
    {
        return $this->balance <= 0;
    }

    /**
     * Re-derive the status from the ledger. Cancelled loans stay cancelled;
     * everything else flips between active and paid on the current balance.
     */
    public function syncStatus(): void
    {
        if ($this->status === EmployeeLoanStatus::Cancelled) {
            return;
        }

        // Read the balance straight from the ledger so a just-written payment
        // (not yet reflected in a loaded relation) is counted.
        $paid = (float) $this->payments()->sum('amount');
        $target = ($paid >= (float) $this->principal_amount)
            ? EmployeeLoanStatus::Paid
            : EmployeeLoanStatus::Active;

        if ($this->status !== $target) {
            $this->update(['status' => $target]);
        }
    }
}
