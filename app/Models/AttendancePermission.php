<?php

namespace App\Models;

use App\Enums\AttendancePermissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An excused-absence request: created by the Admin or Operations Manager and
 * approved by the Admin before payroll runs. Approved days roll into the
 * employee's permitted_days for the period, reducing unpaid absence.
 */
class AttendancePermission extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'file_path',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'review_notes',
        'mass_permission_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
        'status' => AttendancePermissionStatus::class,
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function massPermission(): BelongsTo
    {
        return $this->belongsTo(MassPermission::class);
    }

    public function isPending(): bool
    {
        return $this->status === AttendancePermissionStatus::Pending;
    }
}
