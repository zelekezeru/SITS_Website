<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\BookRequestStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One signature block on the paper form, with a name, a timestamp and an audit
 * trail behind it. Append-only.
 */
class BookRequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_request_id',
        'stage',
        'actor_id',
        'decision',
        'note',
        'acted_at',
    ];

    protected $casts = [
        'stage'    => BookRequestStage::class,
        'decision' => ApprovalDecision::class,
        'acted_at' => 'datetime',
    ];

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
