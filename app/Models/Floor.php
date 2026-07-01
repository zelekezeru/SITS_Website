<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\LogsOperationalActivity;

class Floor extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($floor) {
            if ($floor->isForceDeleting()) {
                $floor->rows()->withTrashed()->get()->each->forceDelete();
            } else {
                $floor->rows()->get()->each->delete();
            }
        });
    }

    protected $fillable = ['campus_id', 'name', 'level'];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(Row::class);
    }
}
