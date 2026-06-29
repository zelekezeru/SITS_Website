<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Evaluation extends Model
{
    protected $fillable = [
        'employee_id',
        'evaluation_period_id',
        'auto_score',
        'manager_score',
        'executive_score',
        'final_score',
        'grade_band_id',
        'status',
    ];

    protected $casts = [
        'auto_score' => 'decimal:2',
        'manager_score' => 'decimal:2',
        'executive_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'status' => EvaluationStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function gradeBand(): BelongsTo
    {
        return $this->belongsTo(GradeBand::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(EvaluationRating::class);
    }

    public function incrementRecommendation(): HasOne
    {
        return $this->hasOne(IncrementRecommendation::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
