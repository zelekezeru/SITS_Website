<?php

namespace App\Models;

use App\Enums\WritingReportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WritingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'integrity_document_id',
        'type',
        'payload',
        'model',
        'token_usage',
    ];

    protected $casts = [
        'type' => WritingReportType::class,
        'payload' => 'array',
        'token_usage' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(IntegrityDocument::class, 'integrity_document_id');
    }
}
