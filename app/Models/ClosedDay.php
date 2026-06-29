<?php

namespace App\Models;

use App\Enums\ClosedDayType;
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
        'date',
        'name',
        'type',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'date'      => 'date',
        'type'      => ClosedDayType::class,
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function massPermissions(): BelongsToMany
    {
        return $this->belongsToMany(MassPermission::class, 'closed_day_mass_permission');
    }
}
