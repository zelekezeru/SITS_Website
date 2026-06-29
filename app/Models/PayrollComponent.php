<?php

namespace App\Models;

use App\Enums\ComponentSide;
use App\Enums\PayrollComponentCalc;
use App\Enums\PayrollComponentKind;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An admin-configurable payroll "frame": an allowance title, a deduction title,
 * or a statutory contribution (pension / provident fund). Per-employee amounts
 * for allowance/deduction kinds live on PayrollComponentAssignment; statutory
 * components apply globally to the employees their `applies_to` rule selects.
 */
class PayrollComponent extends Model
{
    use Blameable;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'rate', 'calc_type', 'side', 'applies_to', 'taxable', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payroll-config');
    }

    protected $fillable = [
        'name',
        'kind',
        'calc_type',
        'rate',
        'side',
        'applies_to',
        'taxable',
        'exempt_capped',
        'sheet_column',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'kind' => PayrollComponentKind::class,
        'calc_type' => PayrollComponentCalc::class,
        'side' => ComponentSide::class,
        'rate' => 'decimal:4',
        'taxable' => 'boolean',
        'exempt_capped' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(PayrollComponentAssignment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeKind(Builder $query, PayrollComponentKind $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function scopeStatutory(Builder $query): Builder
    {
        return $query->where('kind', PayrollComponentKind::Statutory);
    }

    /** Does this statutory component apply to the given employee's scheme membership? */
    public function appliesToEmployee(Employee $employee): bool
    {
        // Staff exempt from both schemes get no statutory contributions.
        if ($employee->statutory_exempt) {
            return false;
        }

        return match ($this->applies_to) {
            'pension_members' => ! $employee->has_provident_fund,
            'pf_members' => (bool) $employee->has_provident_fund,
            default => true,
        };
    }
}
