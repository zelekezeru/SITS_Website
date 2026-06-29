<?php

namespace App\Models;

use App\Enums\ScheduleType;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Attaches an allowance/deduction component to an employee with a schedule that
 * decides which payroll periods it applies to.
 */
class PayrollComponentAssignment extends Model
{
    use Blameable;
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'payroll_component_id',
        'amount',
        'schedule_type',
        'start_period_id',
        'end_period_id',
        'note',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'schedule_type' => ScheduleType::class,
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }

    public function startPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'start_period_id');
    }

    public function endPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'end_period_id');
    }

    /**
     * Whether this assignment applies to a given period, by schedule. Periods are
     * monthly and compared by start_date so we don't depend on id ordering.
     */
    public function appliesTo(PayrollPeriod $period): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $on = $period->start_date;
        $start = $this->startPeriod?->start_date;
        $end = $this->endPeriod?->start_date;

        return match ($this->schedule_type) {
            ScheduleType::OneTime => $start !== null && $on->equalTo($start),
            ScheduleType::Range => ($start === null || $on->greaterThanOrEqualTo($start))
                && ($end === null || $on->lessThanOrEqualTo($end)),
            ScheduleType::Monthly => ($start === null || $on->greaterThanOrEqualTo($start))
                && ($end === null || $on->lessThanOrEqualTo($end)),
        };
    }
}
