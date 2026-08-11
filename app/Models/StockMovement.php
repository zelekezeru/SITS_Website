<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One line of the bin card. Append-only: never updated, never deleted. A mistake
 * is corrected with a compensating movement so the history stays honest.
 *
 * Writes go through {@see \App\Services\Bookstore\StockLedger} only.
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_title_id',
        'shelf_section_id',
        'type',
        'quantity',
        'balance_after',
        'unit_price',
        'total_price',
        'reference_type',
        'reference_id',
        'reference_number',
        'description',
        'remark',
        'performed_by',
        'occurred_at',
    ];

    protected $casts = [
        'type'          => StockMovementType::class,
        'quantity'      => 'integer',
        'balance_after' => 'integer',
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
        'occurred_at'   => 'datetime',
    ];

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    public function shelfSection(): BelongsTo
    {
        return $this->belongsTo(ShelfSection::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** The bin card renders these directly, so they travel with the model. */
    protected $appends = ['signed_quantity', 'quantity_received', 'quantity_issued'];

    /** Signed change this movement made to the section balance. */
    public function getSignedQuantityAttribute(): int
    {
        return $this->quantity * $this->type->sign();
    }

    /** The paper bin card has separate "received" and "issued" columns. */
    public function getQuantityReceivedAttribute(): ?int
    {
        return $this->type->isInbound() ? $this->quantity : null;
    }

    public function getQuantityIssuedAttribute(): ?int
    {
        return $this->type->isInbound() ? null : $this->quantity;
    }

    public function scopeForTitle(Builder $query, int $bookTitleId): Builder
    {
        return $query->where('book_title_id', $bookTitleId);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->where('occurred_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('occurred_at', '<=', $to));
    }

    /** Bin-card order: oldest first, so the running balance reads downward. */
    public function scopeLedgerOrder(Builder $query): Builder
    {
        return $query->orderBy('occurred_at')->orderBy('id');
    }
}
