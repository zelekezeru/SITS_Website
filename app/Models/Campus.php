<?php

namespace App\Models;

use App\Traits\LogsOperationalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unified Campus — shared by the ERP (bilingual name/city + departments) and the
 * Library ILS (code/address + the spatial floor→row→shelf-box hierarchy). One
 * physical campus, one row in `campuses`. (Merged from sits-library.)
 */
class Campus extends Model
{
    use SoftDeletes, HasFactory, LogsOperationalActivity;

    protected static function boot()
    {
        parent::boot();

        // Library: cascade (soft/force) delete to the spatial hierarchy.
        static::deleting(function ($campus) {
            if ($campus->isForceDeleting()) {
                $campus->floors()->withTrashed()->get()->each->forceDelete();
            } else {
                $campus->floors()->get()->each->delete();
            }
        });
    }

    protected $fillable = [
        // ERP (bilingual) fields
        'name_en',
        'name_am',
        'city',
        // Library fields
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── ERP relationships ─────────────────────────────────────────────────
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    // ── Library (spatial) relationships ───────────────────────────────────
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function rows(): HasManyThrough
    {
        return $this->hasManyThrough(Row::class, Floor::class);
    }

    public function shelfBoxCount(): int
    {
        return ShelfBox::whereIn('row_id', $this->rows()->pluck('rows.id'))->count();
    }

    /** Users whose current campus is this one. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_campus_id');
    }
}
