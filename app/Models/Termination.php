<?php

namespace App\Models;

use App\Enums\TerminationReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Termination extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'reason',
        'notes',
        'status',
        'effective_date',
        'initiated_by',
        'initiated_at',
        'finalized_by',
        'finalized_at',
        'severance_amount',
        'severance_notes',
        'final_payslip_id',
        'handover_checklist',
        'handover_completed_at',
    ];

    protected $casts = [
        'reason' => TerminationReason::class,
        'effective_date' => 'date',
        'initiated_at' => 'datetime',
        'finalized_at' => 'datetime',
        'handover_completed_at' => 'datetime',
        'severance_amount' => 'decimal:2',
        'handover_checklist' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function finalPayslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'final_payslip_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function isEffective(): bool
    {
        return $this->effective_date <= now();
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized' || $this->finalized_at !== null;
    }

    public function canFinalize(): bool
    {
        return $this->status === 'pending' && $this->isEffective();
    }

    public function finalize(array $checklistData = []): bool
    {
        if (! $this->canFinalize()) {
            return false;
        }

        return $this->update([
            'status' => 'finalized',
            'finalized_by' => auth()->id(),
            'finalized_at' => now(),
            'handover_checklist' => $checklistData,
            'handover_completed_at' => collect($checklistData)->every(fn ($v) => $v === true) ? now() : null,
        ]);
    }

    public function requiresSeverance(): bool
    {
        return $this->reason->requiresSeverance();
    }
}
