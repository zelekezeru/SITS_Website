<?php

namespace App\Models;

use App\Enums\PayslipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payslip extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'grade',
        'campus',
        'working_days',
        'gross',
        'overtime',
        'mobile_allowance',
        'transport_allowance',
        'housing_allowance',
        'cash_allowance',
        'taxable_income',
        'income_tax',
        'employee_pension',
        'employer_pension',
        'provident_fund_employee',
        'provident_fund_employer',
        'salary_advance',
        'kircha_deduction',
        'other_deduction',
        'total_deductions',
        'net_pay',
        'status',
    ];

    protected $casts = [
        'working_days' => 'decimal:2',
        'gross' => 'decimal:2',
        'overtime' => 'decimal:2',
        'mobile_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'cash_allowance' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'employee_pension' => 'decimal:2',
        'employer_pension' => 'decimal:2',
        'provident_fund_employee' => 'decimal:2',
        'provident_fund_employer' => 'decimal:2',
        'salary_advance' => 'decimal:2',
        'kircha_deduction' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'status' => PayslipStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
