<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingCopy extends Model
{
    protected $fillable = [
        'legacy_id',
        'legacy_book_id',
        'source_file',
        'source_row',
        'raw',
        'cleaned',
        'status',
        'errors',
        'book_copy_id',
    ];

    protected $casts = [
        'raw' => 'array',
        'cleaned' => 'array',
    ];
}
