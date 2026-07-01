<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fine extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'user_id',
        'loan_id',
        'reason',
        'amount',
        'paid_amount',
        'status',
        'note',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $appends = ['balance'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Outstanding amount still owed. */
    public function getBalanceAttribute(): float
    {
        return round((float) $this->amount - (float) $this->paid_amount, 2);
    }

    public function scopeOutstanding($query)
    {
        return $query->where('status', 'open')->whereColumn('paid_amount', '<', 'amount');
    }
}
