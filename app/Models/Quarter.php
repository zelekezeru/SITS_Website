<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quarter extends Model
{
    protected $fillable = [
        'year_id',
        'name',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function fortnights(): HasMany
    {
        return $this->hasMany(Fortnight::class);
    }
}
