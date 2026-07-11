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
        'legal_daily_hour_limit',
        'hired_at',
        'is_active',
        'attendance_exempt',
        'attendance_exempt_reason',
        'status',
    ];

    protected $casts = [
        'employment_type' => EmploymentType::class,
        'status' => EmployeeStatus::class,
        'base_salary' => 'decimal:2',
        'has_provident_fund' => 'boolean',
        'statutory_exempt' => 'boolean',
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
}
