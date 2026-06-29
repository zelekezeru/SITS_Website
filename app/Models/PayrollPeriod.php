<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollPeriod extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'prepared_by', 'approved_by', 'payment_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payroll');
    }

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'payment_date',
        'prepared_by',
        'prepared_at',
        'submitted_at',
        'approved_by',
        'approved_at',
        'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'prepared_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'status' => PayrollStatus::class,
    ];

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function attendancePermissions(): HasMany
    {
        return $this->hasMany(AttendancePermission::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Only full calendar-month periods (excludes the fortnightly periods).
     *
     * A monthly period starts on the 1st and ends on the last day of the month
     * (28–31). Fortnightly periods that begin on the 1st always end on the 14th,
     * so the end-day check cleanly separates the two. Uses whereDay so it stays
     * portable across MySQL (prod) and SQLite (tests).
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query
            ->whereDay('start_date', 1)
            ->where(fn (Builder $q) => $q
                ->whereDay('end_date', 28)
                ->orWhereDay('end_date', 29)
                ->orWhereDay('end_date', 30)
                ->orWhereDay('end_date', 31));
    }

    /** Restrict to the active fiscal year's date range (no-op if none active). */
    public function scopeForActiveYear(Builder $query): Builder
    {
        $year = Year::active();

        return $query->when($year, fn (Builder $q) => $q
            ->whereDate('start_date', '>=', $year->start_date)
            ->whereDate('end_date', '<=', $year->end_date));
    }

    /** A locked or paid period is immutable. */
    public function isLocked(): bool
    {
        return \in_array($this->status, [PayrollStatus::Locked, PayrollStatus::Paid], true);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === PayrollStatus::PendingApproval;
    }

    public function isApproved(): bool
    {
        return \in_array($this->status, [PayrollStatus::Approved, PayrollStatus::Locked, PayrollStatus::Paid], true);
    }

    /**
     * Finance may prepare/edit a period only while it's open, processing or was
     * rejected back to them — never once it's awaiting approval, approved or locked.
     */
    public function canBeEditedByFinance(): bool
    {
        return \in_array($this->status, [
            PayrollStatus::Open,
            PayrollStatus::Processing,
            PayrollStatus::Rejected,
        ], true);
    }
}
