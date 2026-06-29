<?php

namespace App\Models;

use App\Enums\IncrementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncrementRecommendation extends Model
{
    protected $fillable = [
        'evaluation_id',
        'current_salary',
        'proposed_salary',
        'status',
        'approved_by_id',
        'applied_payroll_period_id',
    ];

    protected $casts = [
        'current_salary' => 'decimal:2',
        'proposed_salary' => 'decimal:2',
        'status' => IncrementStatus::class,
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function appliedPayrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'applied_payroll_period_id');
    }
}
