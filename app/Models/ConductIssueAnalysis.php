<?php

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConductIssueAnalysis extends Model
{
    protected $fillable = [
        'conduct_issue_id',
        'provider',
        'model',
        'severity_assessment',
        'confidence',
        'risk_level',
        'suggested_actions',
        'escalation_needed',
        'investigation_required',
        'warnings',
        'human_confirmed',
        'confirmed_by_id',
    ];

    protected $casts = [
        'provider' => AiProvider::class,
        'confidence' => 'float',
        'suggested_actions' => 'array',
        'escalation_needed' => 'boolean',
        'investigation_required' => 'boolean',
        'warnings' => 'array',
        'human_confirmed' => 'boolean',
    ];

    public function conductIssue(): BelongsTo
    {
        return $this->belongsTo(ConductIssue::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_id');
    }
}
