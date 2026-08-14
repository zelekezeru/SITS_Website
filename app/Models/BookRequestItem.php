<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_request_id',
        'book_title_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_dispatched',
        'unit_price',
        'line_total',
        'remark',
    ];

    protected $casts = [
        'quantity_requested'  => 'integer',
        'quantity_approved'   => 'integer',
        'quantity_dispatched' => 'integer',
        'unit_price'          => 'decimal:2',
        'line_total'          => 'decimal:2',
    ];

    protected $appends = ['quantity_outstanding'];

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    /** Approved but not yet out of the door — what the store still owes. */
    public function getQuantityOutstandingAttribute(): int
    {
        return max(0, $this->quantity_approved - $this->quantity_dispatched);
    }

    /** Recompute the line total from the approved (or requested) quantity. */
    public function refreshTotals(): void
    {
        $quantity = $this->quantity_approved ?: $this->quantity_requested;

        $this->line_total = round($quantity * (float) $this->unit_price, 2);
    }
}
