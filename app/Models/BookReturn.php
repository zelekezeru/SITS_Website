<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Unsold copies coming back from a centre or campus — the right-hand columns of
 * the paper "የመጽሃፍ መመዝገቢያ ቅጽ" (returned / not returned).
 */
class BookReturn extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'return_number',
        'book_dispatch_id',
        'center_id',
        'campus_id',
        'shelf_section_id',
        'returned_on',
        'received_by',
        'returned_by_name',
        'condition_note',
        'total_quantity',
    ];

    protected $casts = [
        'returned_on'    => 'date',
        'total_quantity' => 'integer',
    ];

    public function bookDispatch(): BelongsTo
    {
        return $this->belongsTo(BookDispatch::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function shelfSection(): BelongsTo
    {
        return $this->belongsTo(ShelfSection::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookReturnItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /** Next sequential return number, e.g. BRT-2026-0007. */
    public static function nextNumber(): string
    {
        $year   = now()->year;
        $prefix = "BRT-{$year}-";

        $last = static::where('return_number', 'like', $prefix.'%')
            ->orderByDesc('return_number')
            ->value('return_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
