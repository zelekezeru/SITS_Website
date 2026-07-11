<?php

namespace App\Models;

use App\Enums\EmployeeLoanPaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One repayment against an EmployeeLoan — either auto-withheld on a payroll run
 * (linked to its period) or a manual extra/settlement payment (period null).
 */
class EmployeeLoanPayment extends Model
{
    protected $fillable = [
        'employee_loan_id',
        'payroll_period_id',
        'amount',
        'type',
        'note',
        'paid_on',
        'created_by',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'type'     => EmployeeLoanPaymentType::class,
        'paid_on'  => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
