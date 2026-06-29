<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Day extends Model
{
    protected $fillable = [
        'date',
        'fortnight_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fortnight(): BelongsTo
    {
        return $this->belongsTo(Fortnight::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'day_task')->withTimestamps();
    }
}
