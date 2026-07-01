<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureDocumentView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'secure_document_id',
        'user_id',
        'ip',
        'user_agent',
        'pages_viewed',
        'opened_at',
        'last_seen_at',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'secure_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
