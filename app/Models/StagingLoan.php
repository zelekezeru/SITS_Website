<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingLoan extends Model
{
    protected $fillable = [
        'legacy_id',
        'source_file',
        'raw',
        'status',
    ];

    protected $casts = [
        'raw' => 'array',
    ];
}
