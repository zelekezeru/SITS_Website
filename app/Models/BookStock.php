<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Balance of one title at one shelf section.
 *
 * This is a CACHE. `stock_movements` is the truth — any balance here can be
 * recomputed from the ledger, which is what makes an audit defensible.
 */
class BookStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_title_id',
        'shelf_section_id',
        'quantity',
        'reserved_quantity',
        'last_counted_at',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
        'last_counted_at'   => 'datetime',
    ];

    protected $appends = ['available'];

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    public function shelfSection(): BelongsTo
    {
        return $this->belongsTo(ShelfSection::class);
    }

    /** On hand minus what is already promised to a verified request. */
    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
