<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Target extends Model
{
    protected $fillable = [
        'goal_id',
        'year_id',
        'name',
        'budget',
        'value',
        'unit',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_target')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** KPIs owned by this strategic target. */
    public function kpis(): MorphMany
    {
        return $this->morphMany(Kpi::class, 'kpiable');
    }
}
