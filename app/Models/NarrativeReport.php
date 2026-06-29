<?php

namespace App\Models;

use App\Enums\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NarrativeReport extends Model
{
    protected $fillable = [
        'employee_id',
        'evaluation_period_id',
        'language',
        'body',
    ];

    protected $casts = [
        'language' => Language::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class);
    }

    public function taskProgressReports(): HasMany
    {
        return $this->hasMany(TaskProgressReport::class);
    }
}
