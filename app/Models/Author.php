<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'slug'];

    protected static function booted()
    {
        static::creating(function ($author) {
            if (!$author->slug) {
                $author->slug = \Illuminate\Support\Str::slug($author->name);
            }
        });
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
