<?php

namespace App\Models;

use App\Enums\ConductIssueType;
use App\Enums\ConductSeverity;
use App\Enums\ConductStatus;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConductIssue extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'issue_type',
        'severity',
        'status',
        'description_en',
        'description_am',
        'justification',
        'incident_date',
        'location',
        'witnesses',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'issue_type' => ConductIssueType::class,
        'severity' => ConductSeverity::class,
        'status' => ConductStatus::class,
        'incident_date' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'witnesses' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** The user who reported this issue (stamped by Blameable). */
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

    public function decision(): HasOne
    {
        return $this->hasOne(ConductDecision::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function isDraft(): bool
    {
        return $this->status === ConductStatus::Draft;
    }

    public function isPending(): bool
    {
        return $this->status === ConductStatus::Submitted || $this->status === ConductStatus::UnderReview;
    }

    public function isApproved(): bool
    {
        return $this->status === ConductStatus::Approved;
    }

    public function isFinalized(): bool
    {
        return $this->status->isFinal();
    }

    public function submit(): bool
    {
        if ($this->status !== ConductStatus::Draft) {
            return false;
        }

        return $this->update(['status' => ConductStatus::Submitted]);
    }

    public function approve(?string $notes = null): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => ConductStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function reject(string $reason): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => ConductStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
