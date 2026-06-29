<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobDescription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'position_id',
        'title_en',
        'title_am',
        'current_version_id',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(JobDescriptionVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionVersion::class, 'current_version_id');
    }
}
