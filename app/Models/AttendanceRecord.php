<?php

namespace App\Models;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'source',
        'work_hours',
        'late_minutes',
        'absent_days',
        'permitted_days',
        'overtime_normal',
        'ot_night',
        'ot_rest',
        'ot_holiday',
        'status',
    ];

    protected $casts = [
        'source' => AttendanceSource::class,
        'status' => AttendanceStatus::class,
        'work_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'absent_days' => 'integer',
        'permitted_days' => 'integer',
        'overtime_normal' => 'decimal:2',
        'ot_night' => 'decimal:2',
        'ot_rest' => 'decimal:2',
        'ot_holiday' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function isLocked(): bool
    {
        return $this->status === AttendanceStatus::Locked;
    }
}
