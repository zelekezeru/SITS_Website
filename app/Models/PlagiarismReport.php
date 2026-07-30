<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlagiarismReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'integrity_document_id',
        'overall_similarity',
        'web_similarity',
        'matches',
        'corpus_size',
        'analyzed_at',
    ];

    protected $casts = [
        'overall_similarity' => 'integer',
        'web_similarity' => 'integer',
        'matches' => 'array',
        'corpus_size' => 'integer',
        'analyzed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(IntegrityDocument::class, 'integrity_document_id');
    }
}
