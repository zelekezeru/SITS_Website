<?php

namespace App\Models;

use App\Enums\BookDispatchStatus;
use App\Models\Concerns\HasTrackingHash;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A consignment leaving the store — the waybill / property handover note
 * ("ንብረት መረካከቢያ ፎርም"). Carries a QR the receiver scans to confirm delivery.
 */
class BookDispatch extends Model
{
    use HasFactory, HasTrackingHash, LogsOperationalActivity;

    protected $fillable = [
        'dispatch_number',
        'book_request_id',
        'dispatched_by',
        'dispatched_at',
        'received_by_name',
        'received_by_phone',
        'received_by_user_id',
        'received_at',
        'receipt_signature_path',
        'status',
        'total_quantity',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'status'         => BookDispatchStatus::class,
        'dispatched_at'  => 'datetime',
        'received_at'    => 'datetime',
        'total_quantity' => 'integer',
        'total_amount'   => 'decimal:2',
    ];

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookDispatchItem::class);
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(BookReturn::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function getQrLabelAttribute(): string
    {
        return $this->dispatch_number;
    }

    public function isReceived(): bool
    {
        return $this->status === BookDispatchStatus::RECEIVED;
    }

    /** Next sequential waybill number, e.g. BD-2026-0007. */
    public static function nextNumber(): string
    {
        $year   = now()->year;
        $prefix = "BD-{$year}-";

        $last = static::where('dispatch_number', 'like', $prefix.'%')
            ->orderByDesc('dispatch_number')
            ->value('dispatch_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
