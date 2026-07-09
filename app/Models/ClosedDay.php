<?php

namespace App\Models;

use App\Enums\ClosedDayType;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A declared non-working day (public holiday, special closure, official closure).
 *
 * Closed days are attached to Mass Permission requests to auto-generate
 * excused absence records for all active employees in a payroll period.
 */
class ClosedDay extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'name',
        'type',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'type'       => ClosedDayType::class,
        'is_active'  => 'boolean',
    ];

    /** Inclusive number of calendar days the closure spans (1 for a single day). */
    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Every calendar date the closure covers, as `Y-m-d` strings. */
    public function dates(): array
    {
        return collect(CarbonPeriod::create($this->start_date, $this->end_date))
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->all();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function massPermissions(): BelongsToMany
    {
        return $this->belongsToMany(MassPermission::class, 'closed_day_mass_permission');
    }
}
