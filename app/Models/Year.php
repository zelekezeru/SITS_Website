<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Year extends Model
{
    protected $fillable = [
        'label',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    public function strategies(): HasMany
    {
        return $this->hasMany(Strategy::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(Target::class);
    }

    public function quarters(): HasMany
    {
        return $this->hasMany(Quarter::class);
    }

    /** The single active fiscal year, if any. */
    public static function active(): ?self
    {
        return static::where('active', true)->first();
    }
}
