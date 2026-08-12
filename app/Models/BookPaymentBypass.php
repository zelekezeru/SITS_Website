<?php

namespace App\Models;

use App\Enums\PaymentBypassStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A "pay later" deferral against a book request.
 *
 * Finance raises it with a reason; an authoriser approves it with a written
 * justification. Approving does not forgive the money — it releases the payment
 * gate and leaves an outstanding debt that stays on the deferred-payment report
 * until someone settles it.
 */
class BookPaymentBypass extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'reference',
        'book_request_id',
        'amount',
        'promised_on',
        'reason',
        'status',
        'requested_by',
        'requested_at',
        'decided_by',
        'decided_at',
        'justification',
        'rejection_reason',
        'settled_at',
        'settled_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'status'       => PaymentBypassStatus::class,
        'promised_on'  => 'date',
        'requested_at' => 'datetime',
        'decided_at'   => 'datetime',
        'settled_at'   => 'datetime',
    ];

    protected $appends = ['is_overdue'];

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function isPending(): bool
    {
        return $this->status === PaymentBypassStatus::PENDING;
    }

    /** Past the date Finance promised, and still unpaid. */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status->isOutstandingDebt()
            && $this->promised_on !== null
            && $this->promised_on->isPast();
    }

    /** How long the authoriser took (or has taken so far) to decide. */
    public function getDecisionWaitSecondsAttribute(): int
    {
        return (int) $this->requested_at->diffInSeconds($this->decided_at ?? now());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentBypassStatus::PENDING->value);
    }

    /** Approved deferrals that are still money owed. */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('status', PaymentBypassStatus::APPROVED->value);
    }

    public static function nextReference(): string
    {
        $year   = now()->year;
        $prefix = "PB-{$year}-";

        $last = static::where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
