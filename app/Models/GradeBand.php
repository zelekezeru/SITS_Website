<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeBand extends Model
{
    protected $fillable = [
        'grade_scale_id',
        'label_en',
        'label_am',
        'min_score',
        'max_score',
        'triggers_increment',
        'increment_pct',
        'sort_order',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'triggers_increment' => 'boolean',
        'increment_pct' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /** The band whose [min,max] range contains the given score. */
    public function scopeForScore(Builder $query, float $score): Builder
    {
        return $query->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }
}
