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
        'waited_seconds',
    ];

    protected $casts = [
        'stage'          => BookRequestStage::class,
        'decision'       => ApprovalDecision::class,
        'acted_at'       => 'datetime',
        'waited_seconds' => 'integer',
    ];

    protected $appends = ['waited_for_humans'];

    /** "2d 4h" — the dwell time, phrased the way somebody chasing a lag reads it. */
    public function getWaitedForHumansAttribute(): ?string
    {
        if ($this->waited_seconds === null) {
            return null;
        }

        $seconds = $this->waited_seconds;

        if ($seconds < 60) {
            return "{$seconds}s";
        }
        if ($seconds < 3600) {
            return floor($seconds / 60).'m';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600).'h '.floor(($seconds % 3600) / 60).'m';
        }

        return floor($seconds / 86400).'d '.floor(($seconds % 86400) / 3600).'h';
    }

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
