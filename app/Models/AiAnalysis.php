<?php

namespace App\Models;

use App\Enums\AiAnalysisType;
use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    protected $table = 'ai_analyses';

    protected $fillable = [
        'narrative_report_id',
        'provider',
        'analysis_type',
        'model',
        'summary_en',
        'summary_am',
        'kpi_scores_json',
        'sentiment',
        'risk_flags',
        'human_confirmed',
        'confirmed_by_id',
    ];

    protected $casts = [
        'provider' => AiProvider::class,
        'analysis_type' => AiAnalysisType::class,
        'kpi_scores_json' => 'array',
        'sentiment' => 'array',
        'risk_flags' => 'array',
        'human_confirmed' => 'boolean',
    ];

    public function narrativeReport(): BelongsTo
    {
        return $this->belongsTo(NarrativeReport::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_id');
    }
}
