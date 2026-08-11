<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookDispatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_dispatch_id',
        'book_title_id',
        'shelf_section_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function bookDispatch(): BelongsTo
    {
        return $this->belongsTo(BookDispatch::class);
    }

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    public function shelfSection(): BelongsTo
    {
        return $this->belongsTo(ShelfSection::class);
    }
}
