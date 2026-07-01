<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'stocktake_id',
        'book_copy_id',
        'scanned_by',
        'location_match',
        'found_location',
        'note',
        'scanned_at',
    ];

    protected $casts = [
        'location_match' => 'boolean',
        'scanned_at'     => 'datetime',
    ];

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function scopeMismatched($query)
    {
        return $query->where('location_match', false);
    }
}
