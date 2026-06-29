<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class JobDescriptionVersion extends Model
{
    use Blameable;

    protected $fillable = [
        'job_description_id',
        'version_no',
        'body',
        'items',
        'effective_from',
    ];

    protected $casts = [
        'version_no' => 'integer',
        'items' => 'array',
        'effective_from' => 'date',
    ];

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** KPIs owned by this JD version (role-based KPIs). */
    public function kpis(): MorphMany
    {
        return $this->morphMany(Kpi::class, 'kpiable');
    }
}
