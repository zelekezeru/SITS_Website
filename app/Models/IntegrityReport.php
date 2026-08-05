<?php

namespace App\Models;

use App\Enums\IntegrityConfidence;
use App\Enums\IntegrityReviewStatus;
use App\Enums\IntegrityVerdict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'integrity_document_id',
        'ai_probability',
        'confidence',
        'verdict_label',
        'statistical_signals',
        'claude_analysis',
        'sentence_scores',
        'flagged',
        'review_status',
        'reviewed_by',
        'review_notes',
        'student_meeting_date',
        'reviewed_at',
        'engine_version',
        'analyzed_at',
    ];

    protected $casts = [
        'ai_probability' => 'integer',
        'confidence' => IntegrityConfidence::class,
        'verdict_label' => IntegrityVerdict::class,
        'statistical_signals' => 'array',
        'claude_analysis' => 'array',
        'sentence_scores' => 'array',
        'flagged' => 'boolean',
        'review_status' => IntegrityReviewStatus::class,
        'student_meeting_date' => 'date',
        'reviewed_at' => 'datetime',
        'analyzed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(IntegrityDocument::class, 'integrity_document_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
