<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsOperationalActivity;

class Row extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($row) {
            if ($row->isForceDeleting()) {
                $row->shelfBoxes()->withTrashed()->get()->each->forceDelete();
            } else {
                $row->shelfBoxes()->get()->each->delete();
            }
        });
    }

    protected $fillable = ['floor_id', 'label', 'subject_area'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function shelfBoxes(): HasMany
    {
        return $this->hasMany(ShelfBox::class);
    }

    /**
     * Campus via floor — convenience for breadcrumb / scope queries.
     */
    public function campus(): ?Campus
    {
        return $this->floor?->campus;
    }
}
