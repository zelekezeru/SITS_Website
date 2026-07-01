<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'fine_id',
        'user_id',
        'recorded_by',
        'amount',
        'currency',
        'method',
        'status',
        'reference',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
        'meta'    => 'array',
    ];

    public function fine(): BelongsTo
    {
        return $this->belongsTo(Fine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
