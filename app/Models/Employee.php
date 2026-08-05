<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'staff_no',
        'device_employee_code',
        'full_name_en',
        'full_name_am',
        'position_id',
        'department_id',
        'reporting_to_id',
        'employment_type',
        'base_salary',
        'grade',
        'has_provident_fund',
        'statutory_exempt',
        'medical_allowance_enabled',
        'medical_allowance_requested',
        'medical_allowance_requested_by',
        'medical_allowance_requested_at',
        'medical_allowance_reviewed_by',
        'medical_allowance_reviewed_at',
        'medical_allowance_rejection_reason',
        'legal_daily_hour_limit',
        'hired_at',
        'is_active',
        'attendance_exempt',
        'attendance_exempt_reason',
        'attendance_exempt_period_id',
        'status',
    ];

    protected $casts = [
        'employment_type' => EmploymentType::class,
        'status' => EmployeeStatus::class,
        'base_salary' => 'decimal:2',
        'has_provident_fund' => 'boolean',
        'statutory_exempt' => 'boolean',
        'medical_allowance_enabled' => 'boolean',
        'medical_allowance_requested' => 'boolean',
        'medical_allowance_requested_at' => 'datetime',
        'medical_allowance_reviewed_at' => 'datetime',
        'legal_daily_hour_limit' => 'integer',
        'hired_at' => 'date',
        'is_active' => 'boolean',
        'attendance_exempt' => 'boolean',
    ];

    /** Campus name resolved through the employee's department. */
    public function campusName(): ?string
    {
        return $this->department?->campus?->name_en;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reportingTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_to_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_to_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function kpis(): BelongsToMany
    {
        return $this->belongsToMany(Kpi::class, 'employee_kpi')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceImportRows(): HasMany
    {
        return $this->hasMany(AttendanceImportRow::class);
    }

    public function componentAssignments(): HasMany
    {
        return $this->hasMany(PayrollComponentAssignment::class);
    }

    /** The single period a one-month attendance exemption is scoped to, if any. */
    public function attendanceExemptPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'attendance_exempt_period_id');
    }

    /**
     * Is this employee exempt from absence deductions for the given period?
     *
     * A permanent exemption (no period scope) applies everywhere. A one-month
     * exemption applies only to the period it was granted for — so an absent
     * month either side is still charged.
     */
    public function isAttendanceExemptFor(?int $payrollPeriodId = null): bool
    {
        if (! $this->attendance_exempt) {
            return false;
        }

        if ($this->attendance_exempt_period_id === null) {
            return true;
        }

        return $payrollPeriodId !== null
            && (int) $this->attendance_exempt_period_id === $payrollPeriodId;
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function attendancePermissions(): HasMany
    {
        return $this->hasMany(AttendancePermission::class);
    }

    public function narrativeReports(): HasMany
    {
        return $this->hasMany(NarrativeReport::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(EmployeeStatusChange::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function conductIssues(): HasMany
    {
        return $this->hasMany(ConductIssue::class);
    }

    public function terminations(): HasMany
    {
        return $this->hasMany(Termination::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    /** Active loans still being repaid, oldest first (payroll deducts these). */
    public function activeLoans(): HasMany
    {
        return $this->loans()
            ->where('status', \App\Enums\EmployeeLoanStatus::Active)
            ->orderBy('created_at');
    }

    public function medicalAllowanceClaims(): HasMany
    {
        return $this->hasMany(MedicalAllowanceClaim::class);
    }

    public function medicalAllowanceRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medical_allowance_requested_by');
    }

    public function medicalAllowanceReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medical_allowance_reviewed_by');
    }

    /** Full-time and enrolled — the only employees who may submit medical allowance claims. */
    public function isMedicalAllowanceEligible(): bool
    {
        return $this->medical_allowance_enabled
            && $this->employment_type === EmploymentType::FullTime;
    }

    /**
     * Enrollment status for display: none (never requested), pending (awaiting
     * admin decision), enrolled (approved and active), or rejected (declined,
     * can be re-requested).
     */
    public function medicalAllowanceStatus(): string
    {
        return match (true) {
            $this->medical_allowance_requested => 'pending',
            $this->medical_allowance_enabled => 'enrolled',
            $this->medical_allowance_reviewed_by !== null => 'rejected',
            default => 'none',
        };
    }

    /** Ask to enroll this employee — takes effect only once an admin approves it. */
    public function requestMedicalAllowance(User $by): void
    {
        $this->update([
            'medical_allowance_requested' => true,
            'medical_allowance_requested_by' => $by->id,
            'medical_allowance_requested_at' => now(),
            'medical_allowance_rejection_reason' => null,
        ]);
    }

    /** Approve a pending enrollment request — this is what actually flips eligibility on. */
    public function approveMedicalAllowance(User $by): void
    {
        $this->update([
            'medical_allowance_enabled' => true,
            'medical_allowance_requested' => false,
            'medical_allowance_reviewed_by' => $by->id,
            'medical_allowance_reviewed_at' => now(),
            'medical_allowance_rejection_reason' => null,
        ]);
    }

    /** Decline a pending enrollment request. The employee stays ineligible. */
    public function rejectMedicalAllowance(User $by, ?string $reason = null): void
    {
        $this->update([
            'medical_allowance_enabled' => false,
            'medical_allowance_requested' => false,
            'medical_allowance_reviewed_by' => $by->id,
            'medical_allowance_reviewed_at' => now(),
            'medical_allowance_rejection_reason' => $reason,
        ]);
    }

    /**
     * Remove an already-enrolled employee from the medical allowance frame —
     * takes effect immediately, no approval needed to revoke. Clears the
     * review trail too, so the employee resets to a clean "none" status
     * rather than misreporting as "rejected" from the earlier approval.
     */
    public function removeMedicalAllowance(): void
    {
        $this->update([
            'medical_allowance_enabled' => false,
            'medical_allowance_requested' => false,
            'medical_allowance_reviewed_by' => null,
            'medical_allowance_reviewed_at' => null,
            'medical_allowance_rejection_reason' => null,
        ]);
    }
}
