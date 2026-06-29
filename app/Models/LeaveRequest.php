<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'status',
        'start_date',
        'end_date',
        'days_requested',
        'days_approved',
        'reason',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'leave_type' => LeaveType::class,
        'status' => LeaveStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** The user who filed this request (stamped by Blameable). */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function isActive(): bool
    {
        return $this->status === LeaveStatus::Approved && now()->between($this->start_date, $this->end_date);
    }

    public function hasStarted(): bool
    {
        return now()->greaterThanOrEqualTo($this->start_date);
    }

    public function hasEnded(): bool
    {
        return now()->greaterThan($this->end_date);
    }

    public function approve(int $daysApproved, ?string $notes = null): bool
    {
        if ($this->status !== LeaveStatus::Submitted) {
            return false;
        }

        return $this->update([
            'status' => LeaveStatus::Approved,
            'days_approved' => $daysApproved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function reject(string $reason): bool
    {
        if ($this->status !== LeaveStatus::Submitted) {
            return false;
        }

        return $this->update([
            'status' => LeaveStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(string $reason): bool
    {
        if ($this->status !== LeaveStatus::Approved) {
            return false;
        }

        return $this->update([
            'status' => LeaveStatus::Cancelled,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }
}
