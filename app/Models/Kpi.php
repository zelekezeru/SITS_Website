<?php

namespace App\Models;

use App\Enums\KpiStatus;
use App\Enums\MeasureType;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * KPIs are standalone and isolated from tasks. Ownership is polymorphic:
 * a JobDescriptionVersion (role-based) or a Target (strategic). Tasks
 * contribute only through the kpi_task pivot, never owning a KPI.
 *
 * Maker-checker lifecycle: created -> approved (dept head) -> confirmed
 * (admin/president). Only confirmed KPIs count toward scores/increments.
 */
class Kpi extends Model
{
    use Blameable, SoftDeletes;

    protected $fillable = [
        'kpiable_type',
        'kpiable_id',
        'title_en',
        'title_am',
        'measure_type',
        'target_value',
        'unit',
        'weight',
        'is_dynamic',
        'status',
        'approved_by',
        'confirmed_by',
    ];

    protected $casts = [
        'measure_type' => MeasureType::class,
        'status' => KpiStatus::class,
        'target_value' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_dynamic' => 'boolean',
    ];

    public function kpiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_kpi')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /** Tasks that contribute toward this KPI (decoupled via pivot). */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'kpi_task')
            ->withPivot('contribution_weight')
            ->withTimestamps();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluationRatings(): HasMany
    {
        return $this->hasMany(EvaluationRating::class);
    }

    /** Only KPIs that have cleared the checker step count toward scoring. */
    public function isConfirmed(): bool
    {
        return $this->confirmed_by !== null;
    }

    public function isApproved(): bool
    {
        return $this->approved_by !== null;
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_by');
    }
}
