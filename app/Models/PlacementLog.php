<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementLog extends Model
{
    protected $fillable = [
        'book_copy_id',
        'from_shelf_box_id',
        'to_shelf_box_id',
        'user_id',
        'reason',
        'note'
    ];

    public $timestamps = false;
    
    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function copy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id');
    }

    public function fromBox(): BelongsTo
    {
        return $this->belongsTo(ShelfBox::class, 'from_shelf_box_id');
    }

    public function toBox(): BelongsTo
    {
        return $this->belongsTo(ShelfBox::class, 'to_shelf_box_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
