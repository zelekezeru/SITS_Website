<?php

namespace App\Models;

use App\Enums\MedicalAllowanceClaimStatus;
use App\Services\MedicalAllowanceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A medical bill reimbursement request. Submitted by Finance with one or more
 * bill documents, approved by an admin (which locks in the covered / employee
 * split for the year's remaining tiers via MedicalAllowanceCalculator), and
 * finally marked paid once Finance disburses the reimbursement.
 */
class MedicalAllowanceClaim extends Model
{
    protected $fillable = [
        'employee_id',
        'reference',
        'policy_year',
        'bill_amount',
        'covered_amount',
        'employee_amount',
        'incident_date',
        'status',
        'notes',
        'rejection_reason',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'paid_by',
        'paid_at',
        'paid_on',
        'payment_reference',
        'payroll_period_id',
    ];

    protected $casts = [
        'policy_year' => 'integer',
        'bill_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'employee_amount' => 'decimal:2',
        'incident_date' => 'date',
        'status' => MedicalAllowanceClaimStatus::class,
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'paid_on' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeReservesCoverage(Builder $query): Builder
    {
        return $query->whereIn('status', [
            MedicalAllowanceClaimStatus::Approved->value,
            MedicalAllowanceClaimStatus::Paid->value,
        ]);
    }

    /** Approved + paid covered_amount already booked for an employee in a policy year. */
    public static function reservedTotalForYear(int $employeeId, int $policyYear, ?int $excludingClaimId = null): float
    {
        return (float) static::query()
            ->where('employee_id', $employeeId)
            ->where('policy_year', $policyYear)
            ->reservesCoverage()
            ->when($excludingClaimId, fn ($q) => $q->where('id', '!=', $excludingClaimId))
            ->sum('covered_amount');
    }

    public function isPending(): bool
    {
        return $this->status === MedicalAllowanceClaimStatus::PendingReview;
    }

    public function isEditable(): bool
    {
        return $this->isPending();
    }

    /** Documents may be attached any time before disbursement. */
    public function acceptsDocuments(): bool
    {
        return in_array($this->status, [
            MedicalAllowanceClaimStatus::PendingReview,
            MedicalAllowanceClaimStatus::Approved,
        ], true);
    }

    /**
     * Lock in the covered/employee split against what's left of this year's
     * tiers (excluding this claim's own prior reservation, if any) and move to
     * Approved.
     */
    public function approve(User $reviewer, ?string $notes = null): void
    {
        $priorReserved = static::reservedTotalForYear($this->employee_id, $this->policy_year, $this->id);
        $split = MedicalAllowanceCalculator::fromSettings()->split($priorReserved, (float) $this->bill_amount);

        $this->update([
            'covered_amount' => $split['covered_amount'],
            'employee_amount' => $split['employee_amount'],
            'status' => MedicalAllowanceClaimStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => $notes ?: $this->notes,
        ]);
    }

    public function reject(User $reviewer, string $reason): void
    {
        $this->update([
            'status' => MedicalAllowanceClaimStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => MedicalAllowanceClaimStatus::Cancelled]);
    }

    /**
     * Record the reimbursement as disbursed and attribute it to a payroll
     * period so it surfaces on that period's payslip.
     */
    public function recordPayment(User $by, string $paidOn, ?int $payrollPeriodId, ?string $reference = null): void
    {
        $this->update([
            'status' => MedicalAllowanceClaimStatus::Paid,
            'paid_by' => $by->id,
            'paid_at' => now(),
            'paid_on' => $paidOn,
            'payment_reference' => $reference,
            'payroll_period_id' => $payrollPeriodId,
        ]);
    }
}
