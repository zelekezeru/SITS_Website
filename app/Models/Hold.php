<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\LogsOperationalActivity;

class Hold extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'book_id',
        'user_id',
        'campus_id',
        'placed_at',
        'available_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'available_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
