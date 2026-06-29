<?php

namespace App\Models;

use App\Enums\Cadence;
use App\Enums\EvaluationPeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    protected $fillable = [
        'name',
        'cadence',
        'start_date',
        'end_date',
        'status',
        'formula_version',
    ];

    protected $casts = [
        'cadence' => Cadence::class,
        'status' => EvaluationPeriodStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function narrativeReports(): HasMany
    {
        return $this->hasMany(NarrativeReport::class);
    }

    /** A locked period is immutable. */
    public function isLocked(): bool
    {
        return $this->status === EvaluationPeriodStatus::Locked;
    }
}
