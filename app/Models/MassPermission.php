<?php

namespace App\Models;

use App\Enums\MassPermissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A batch excused-absence request covering all active employees for one or more
 * declared closed days within a payroll period.
 *
 * Status machine (two-layer approval):
 *   draft → pending_approval → pending_confirmation → approved / rejected
 *
 * On final approval the controller spawns one AttendancePermission per active
 * employee (pre-approved, days = total_days) so payroll picks them up unchanged.
 */
class MassPermission extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'initiated_by', 'first_approved_by', 'final_approved_by', 'permissions_spawned'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('mass-permission');
    }

    protected $fillable = [
        'name',
        'reason',
        'payroll_period_id',
        'status',
        'total_days',
        'initiated_by',
        'submitted_at',
        'first_approved_by',
        'first_approved_at',
        'first_review_notes',
        'final_approved_by',
        'final_approved_at',
        'final_review_notes',
        'employees_affected',
        'permissions_spawned',
    ];

    protected $casts = [
        'status'              => MassPermissionStatus::class,
        'submitted_at'        => 'datetime',
        'first_approved_at'   => 'datetime',
        'final_approved_at'   => 'datetime',
        'total_days'          => 'integer',
        'employees_affected'  => 'integer',
        'permissions_spawned' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function firstApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_approved_by');
    }

    public function finalApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }

    public function closedDays(): BelongsToMany
    {
        return $this->belongsToMany(ClosedDay::class, 'closed_day_mass_permission');
    }

    public function spawnedPermissions(): HasMany
    {
        return $this->hasMany(AttendancePermission::class);
    }

    // ── State helpers ──────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === MassPermissionStatus::Draft;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === MassPermissionStatus::PendingApproval;
    }

    public function isPendingConfirmation(): bool
    {
        return $this->status === MassPermissionStatus::PendingConfirmation;
    }

    public function isApproved(): bool
    {
        return $this->status === MassPermissionStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === MassPermissionStatus::Rejected;
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft();
    }

    public function canBeFirstApproved(): bool
    {
        return $this->isPendingApproval();
    }

    public function canBeFinalApproved(): bool
    {
        return $this->isPendingConfirmation();
    }

    public function canBeRejected(): bool
    {
        return $this->isPendingApproval() || $this->isPendingConfirmation();
    }
}
