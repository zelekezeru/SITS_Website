<?php

namespace App\Services\Payroll;

use App\Enums\EmployeeLoanPaymentType;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\PayrollPeriod;
use App\Models\User;

/**
 * Applies loan repayments — the auto payroll deduction and manual extra/lump-sum
 * payments — keeping the payment ledger the single source of truth so the
 * outstanding balance always reconciles.
 */
class EmployeeLoanService
{
    /**
     * Withhold this period's repayment for a loan and record it in the ledger.
     *
     * Idempotent: re-running payroll for the same period updates the same ledger
     * row rather than stacking duplicates. The deduction is the fixed monthly
     * amount, capped at the remaining balance and at whatever net pay is left.
     *
     * @param  float  $availableNet  net pay left after all other deductions
     * @return float  the amount actually withheld (0 if nothing was due)
     */
    public function applyPayrollDeduction(EmployeeLoan $loan, PayrollPeriod $period, float $availableNet, ?User $by = null): float
    {
        $existing = $loan->payments()
            ->where('payroll_period_id', $period->id)
            ->where('type', EmployeeLoanPaymentType::Payroll)
            ->first();

        // Balance owed ignoring any deduction already booked for THIS period, so
        // a recompute recalculates from a clean slate.
        $paidElsewhere = (float) $loan->payments()->sum('amount') - (float) ($existing->amount ?? 0);
        $balance = round((float) $loan->principal_amount - $paidElsewhere, 2);

        $deduction = min((float) $loan->monthly_amount, $balance, max($availableNet, 0));
        $deduction = round($deduction, 2);

        if ($deduction <= 0) {
            // Nothing to take this month (settled, or no net pay left): drop any
            // stale row from a previous run so the ledger stays accurate.
            $existing?->delete();
            $loan->syncStatus();

            return 0.0;
        }

        $loan->payments()->updateOrCreate(
            ['payroll_period_id' => $period->id],
            [
                'amount'     => $deduction,
                'type'       => EmployeeLoanPaymentType::Payroll,
                'note'       => 'Auto payroll deduction',
                'paid_on'    => $period->end_date ?? $period->start_date ?? now(),
                'created_by' => $by?->id,
            ],
        );

        $loan->syncStatus();

        return $deduction;
    }

    /**
     * Record a manual repayment (extra amount, or paying the balance off at
     * once). Capped at the outstanding balance so a loan can't be overpaid.
     *
     * @return EmployeeLoanPayment|null  the payment, or null if nothing was owed
     */
    public function recordManualPayment(EmployeeLoan $loan, float $amount, ?User $by = null, ?string $note = null): ?EmployeeLoanPayment
    {
        $amount = round(min($amount, $loan->balance), 2);

        if ($amount <= 0) {
            return null;
        }

        $payment = $loan->payments()->create([
            'payroll_period_id' => null,
            'amount'            => $amount,
            'type'              => EmployeeLoanPaymentType::Manual,
            'note'              => $note ?: 'Manual payment',
            'paid_on'           => now(),
            'created_by'        => $by?->id,
        ]);

        $loan->syncStatus();

        return $payment;
    }
}
