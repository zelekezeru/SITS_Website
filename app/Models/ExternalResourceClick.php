<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalResourceClick extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_resource_id',
        'user_id',
        'ip',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ExternalResource::class, 'external_resource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
