<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A distribution centre (ማእከል) — the header block of the paper request form:
 * centre name, student count, coordinator name and mobile.
 */
class Center extends Model
{
    use HasFactory, LogsOperationalActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'city',
        'region',
        'coordinator_name',
        'coordinator_phone',
        'coordinator_user_id',
        'student_count',
        'campus_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'student_count' => 'integer',
        'is_active'     => 'boolean',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function bookRequests(): HasMany
    {
        return $this->hasMany(BookRequest::class);
    }

    public function bookReturns(): HasMany
    {
        return $this->hasMany(BookReturn::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
