<?php

namespace App\Models;

use App\Enums\StockAuditStatus;
use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A physical count of one store room. Corrections reach the ledger only when an
 * approver signs off the variance — a counter never moves stock alone.
 */
class StockAudit extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'reference',
        'store_room_id',
        'status',
        'started_by',
        'started_at',
        'completed_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'status'       => StockAuditStatus::class,
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function storeRoom(): BelongsTo
    {
        return $this->belongsTo(StoreRoom::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockAuditLine::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function getCountedLinesAttribute(): int
    {
        return $this->lines()->whereNotNull('counted_quantity')->count();
    }

    public function getProgressAttribute(): float
    {
        $total = $this->lines()->count();

        return $total === 0 ? 100.0 : round(($this->counted_lines / $total) * 100, 1);
    }

    /** Lines where the count disagreed with the system. */
    public function variances(): HasMany
    {
        return $this->lines()
            ->whereNotNull('counted_quantity')
            ->whereColumn('counted_quantity', '!=', 'system_quantity');
    }

    public static function nextReference(): string
    {
        $year   = now()->year;
        $prefix = "SA-{$year}-";

        $last = static::where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
