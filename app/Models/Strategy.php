<?php

namespace App\Models;

use App\Enums\StrategicPillar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Strategy extends Model
{
    protected $fillable = [
        'year_id',
        'pillar',
        'name',
        'description',
    ];

    protected $casts = [
        'pillar' => StrategicPillar::class,
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
