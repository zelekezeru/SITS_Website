<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAuditLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_audit_id',
        'shelf_section_id',
        'book_title_id',
        'system_quantity',
        'counted_quantity',
        'counted_by',
        'counted_at',
        'note',
    ];

    protected $casts = [
        'system_quantity'  => 'integer',
        'counted_quantity' => 'integer',
        'counted_at'       => 'datetime',
    ];

    protected $appends = ['variance'];

    public function stockAudit(): BelongsTo
    {
        return $this->belongsTo(StockAudit::class);
    }

    public function shelfSection(): BelongsTo
    {
        return $this->belongsTo(ShelfSection::class);
    }

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /** Positive = more on the shelf than the system thought. Null until counted. */
    public function getVarianceAttribute(): ?int
    {
        return $this->counted_quantity === null
            ? null
            : $this->counted_quantity - $this->system_quantity;
    }

    public function isCounted(): bool
    {
        return $this->counted_quantity !== null;
    }
}
