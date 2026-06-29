<?php

namespace App\Models;

use App\Enums\AttendanceRowMatchMethod;
use App\Enums\AttendanceRowMatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImportRow extends Model
{
    protected $fillable = [
        'attendance_import_id',
        'device_employee_code',
        'device_name',
        'device_department',
        'work_duration_standard_minutes',
        'work_duration_actual_minutes',
        'late_times',
        'late_minutes',
        'leave_early_times',
        'leave_early_minutes',
        'overtime_normal_minutes',
        'overtime_special_minutes',
        'lack_times',
        'lack_minutes',
        'attendance_days_standard',
        'attendance_days_actual',
        'absent_days',
        'remarks',
        'employee_id',
        'match_status',
        'match_method',
        'match_confidence',
        'is_excluded',
        'exclusion_reason',
        'suggested_permitted_days',
    ];

    protected $casts = [
        'match_status' => AttendanceRowMatchStatus::class,
        'match_method' => AttendanceRowMatchMethod::class,
        'match_confidence' => 'decimal:2',
        'is_excluded' => 'boolean',
    ];

    public function attendanceImport(): BelongsTo
    {
        return $this->belongsTo(AttendanceImport::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isResolvable(): bool
    {
        return $this->is_excluded || $this->match_status === AttendanceRowMatchStatus::Matched;
    }
}
