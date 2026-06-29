<?php

namespace App\Models;

use App\Enums\ConductDecision as ConductDecisionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConductDecision extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conduct_issue_id',
        'decided_by',
        'decision',
        'decision_notes_en',
        'decision_notes_am',
        'effective_date',
        'decided_at',
        'status',
        'appealed_by',
        'appeal_date',
        'appeal_notes',
        'overturned_by',
        'overturned_at',
        'overturn_reason',
        'expires_at',
    ];

    protected $casts = [
        'decision' => ConductDecisionEnum::class,
        'effective_date' => 'datetime',
        'decided_at' => 'datetime',
        'appeal_date' => 'datetime',
        'overturned_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function conductIssue(): BelongsTo
    {
        return $this->belongsTo(ConductIssue::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function appealedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appealed_by');
    }

    public function overturnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overturned_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function isAppealed(): bool
    {
        return $this->status === 'appealed' && $this->appealed_by !== null;
    }

    public function isOverturned(): bool
    {
        return $this->status === 'overturned' && $this->overturned_by !== null;
    }

    public function appeal(string $notes): bool
    {
        if ($this->isAppealed() || $this->isOverturned()) {
            return false;
        }

        return $this->update([
            'status' => 'appealed',
            'appealed_by' => auth()->id(),
            'appeal_date' => now(),
            'appeal_notes' => $notes,
        ]);
    }

    public function overturn(string $reason): bool
    {
        if ($this->isOverturned()) {
            return false;
        }

        return $this->update([
            'status' => 'overturned',
            'overturned_by' => auth()->id(),
            'overturned_at' => now(),
            'overturn_reason' => $reason,
        ]);
    }

    public function requiresEmployeeStatusChange(): bool
    {
        return $this->decision->requiresEmployeeStatusChange();
    }

    public function getTargetEmployeeStatus(): ?string
    {
        return $this->decision->targetEmployeeStatus();
    }
}
