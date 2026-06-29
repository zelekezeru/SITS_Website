<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fortnight extends Model
{
    protected $fillable = [
        'quarter_id',
        'name',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(Quarter::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(Day::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'fortnight_task')->withTimestamps();
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    /** The canonical sprint unit: the fortnight covering today. */
    public static function current(?\DateTimeInterface $on = null): ?self
    {
        $date = $on ?? now();

        return static::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }
}
