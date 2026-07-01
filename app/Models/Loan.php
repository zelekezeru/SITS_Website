<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\LogsOperationalActivity;

class Loan extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'book_copy_id',
        'user_id',
        'checked_out_by',
        'checked_out_at_campus_id',
        'checked_out_at',
        'due_on',
        'returned_at',
        'returned_to_campus_id',
        'returned_by',
        'renewal_count',
        'status',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'due_on' => 'date',
        'returned_at' => 'datetime',
    ];

    public function copy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function checkedOutCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'checked_out_at_campus_id');
    }

    public function returnedToCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'returned_to_campus_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    /** Whether this loan is currently past its due date and still active. */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active' && $this->due_on && today()->gt($this->due_on);
    }

    /** Number of calendar days this loan is overdue (0 if not overdue). */
    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return (int) $this->due_on->startOfDay()->diffInDays(today()->startOfDay());
    }
}
