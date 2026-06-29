<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskProgressReport extends Model
{
    protected $fillable = [
        'task_id',
        'completion_pct',
        'narrative_report_id',
    ];

    protected $casts = [
        'completion_pct' => 'decimal:2',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function narrativeReport(): BelongsTo
    {
        return $this->belongsTo(NarrativeReport::class);
    }
}
