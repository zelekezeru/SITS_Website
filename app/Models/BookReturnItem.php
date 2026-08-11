<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_return_id',
        'book_title_id',
        'quantity_returned',
        'quantity_damaged',
        'remark',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
        'quantity_damaged'  => 'integer',
    ];

    public function bookReturn(): BelongsTo
    {
        return $this->belongsTo(BookReturn::class);
    }

    public function bookTitle(): BelongsTo
    {
        return $this->belongsTo(BookTitle::class);
    }

    /** Copies fit to go back on the shelf; damaged ones are written off instead. */
    public function getQuantityResaleableAttribute(): int
    {
        return max(0, $this->quantity_returned - $this->quantity_damaged);
    }
}
