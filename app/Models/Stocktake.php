<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stocktake extends Model
{
    use HasFactory, LogsOperationalActivity;

    protected $fillable = [
        'campus_id',
        'started_by',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(StocktakeScan::class);
    }

    /** How many copies at this campus were expected to be scanned. */
    public function getExpectedCountAttribute(): int
    {
        return BookCopy::where('status', '!=', 'lost')
            ->atCampus($this->campus_id)
            ->count();
    }

    /** Percentage of expected copies that have been scanned. */
    public function getProgressAttribute(): float
    {
        $expected = $this->expected_count;
        if ($expected === 0) return 100.0;
        return round(($this->scans()->count() / $expected) * 100, 1);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
