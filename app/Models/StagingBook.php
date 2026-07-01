<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingBook extends Model
{
    protected $fillable = [
        'legacy_id',
        'source_file',
        'source_row',
        'raw',
        'cleaned',
        'status',
        'errors',
        'book_id',
    ];

    protected $casts = [
        'raw' => 'array',
        'cleaned' => 'array',
    ];
}
