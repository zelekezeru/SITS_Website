<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorpusFingerprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'integrity_document_id',
        'shingle_hash',
        'position',
    ];

    protected $casts = [
        'shingle_hash' => 'integer',
        'position' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(IntegrityDocument::class, 'integrity_document_id');
    }
}
