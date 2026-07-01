<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SecureDocumentAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'secure_document_id',
        'grantee_type',
        'grantee_id',
        'expires_at',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'secure_document_id');
    }

    public function grantee(): MorphTo
    {
        return $this->morphTo();
    }
}
