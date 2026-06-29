<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Singleton representing the institution itself, used solely to hang the
 * institution-wide archive off the polymorphic `documents` vault.
 */
class Organization extends Model
{
    protected $fillable = [
        'name',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
