<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'title_en',
        'title_am',
        'code',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function jobDescriptions(): HasMany
    {
        return $this->hasMany(JobDescription::class);
    }
}
