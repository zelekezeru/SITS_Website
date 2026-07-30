<?php

namespace Tests\Feature;

use App\Enums\EmployeeLoanPaymentType;
use App\Enums\EmployeeLoanStatus;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Payroll\EmployeeLoanService;
use App\Services\Payroll\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Loan repayment behaviour during a payroll run. Loans are applied outside
 * PayrollCalculator, after every other deduction has settled, so the guarantees
 * that matter are: oldest loan first, never withhold more than the net pay
 * left, and stay idempotent when a period is recomputed.
 */
class EmployeeLoanPayrollDeductionTest extends TestCase
{
    use RefreshDatabase;

    /** The outstanding balance is derived from the ledger, never stored. */
    public function test_balance_is_derived_from_the_payment_ledger(): void
    {
        $loan = $this->loan(principal: 10000, monthly: 1000);

        $this->assertEquals(10000.0, (float) $loan->balance);

        app(EmployeeLoanService::class)->recordManualPayment($loan, 2500);

        $this->assertEquals(7500.0, (float) $loan->fresh()->balance);
    }

    /** A manual payment can never overpay the loan. */
    public function test_manual_payment_is_capped_at_the_outstanding_balance(): void
    {
        $loan = $this->loan(principal: 3000, monthly: 1000);

        $payment = app(EmployeeLoanService::class)->recordManualPayment($loan, 5000);

        $this->assertEquals(3000.0, (float) $payment->amount);
        $this->assertEquals(0.0, (float) $loan->fresh()->balance);
        $this->assertEquals(EmployeeLoanStatus::Paid, $loan->fresh()->status);
    }

    /** Paying the balance off flips the loan to Paid and stops future deductions. */
    public function test_settled_loan_stops_deducting(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $loan = $this->loan(principal: 1000, monthly: 1000, employee: $employee);

        app(EmployeeLoanService::class)->recordManualPayment($loan, 1000);
        $this->assertEquals(EmployeeLoanStatus::Paid, $loan->fresh()->status);

        $period = $this->period();
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(0.0, (float) $payslip->loan_deduction);
    }

    /** The monthly amount is withheld and shows on the payslip. */
    public function test_payroll_run_withholds_the_monthly_amount(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $this->loan(principal: 10000, monthly: 1500, employee: $employee);

        $period = $this->period();
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(1500.0, (float) $payslip->loan_deduction);
    }

    /** The final instalment takes only what is left, not the full monthly amount. */
    public function test_final_instalment_is_capped_at_the_remaining_balance(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $this->loan(principal: 400, monthly: 1500, employee: $employee);

        $period = $this->period();
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(400.0, (float) $payslip->loan_deduction);
    }

    /** Loan repayment is subtracted from net pay and added to total deductions. */
    public function test_loan_deduction_reduces_net_pay(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);

        $period = $this->period();
        app(PayrollRunService::class)->run($period);
        $before = Payslip::where('employee_id', $employee->id)->firstOrFail();

        $this->loan(principal: 10000, monthly: 1500, employee: $employee);
        app(PayrollRunService::class)->run($period);
        $after = Payslip::where('employee_id', $employee->id)->firstOrFail();

        $this->assertEquals(
            round((float) $before->net_pay - 1500.0, 2),
            round((float) $after->net_pay, 2),
        );
        $this->assertEquals(
            round((float) $before->total_deductions + 1500.0, 2),
            round((float) $after->total_deductions, 2),
        );
        // Gross is untouched — a loan is a post-tax withholding, not a pay cut.
        $this->assertEquals((float) $before->gross, (float) $after->gross);
        $this->assertEquals((float) $before->income_tax, (float) $after->income_tax);
    }

    /** Two loans both deduct, oldest first, and both appear in the total. */
    public function test_multiple_loans_both_deduct(): void
    {
        $employee = $this->employee(['base_salary' => 30000]);
        $this->loan(principal: 10000, monthly: 1000, employee: $employee, createdAt: '2026-01-01');
        $this->loan(principal: 10000, monthly: 800, employee: $employee, createdAt: '2026-03-01');

        $period = $this->period();
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(1800.0, (float) $payslip->loan_deduction);
    }

    /**
     * When net pay cannot cover both loans, the older one is repaid first and
     * the younger takes only what is left — the total can never exceed net pay.
     */
    public function test_older_loan_is_repaid_first_when_net_pay_runs_out(): void
    {
        // A small salary so net pay is the binding constraint, not the balances.
        $employee = $this->employee(['base_salary' => 2000]);
        $older = $this->loan(principal: 50000, monthly: 40000, employee: $employee, createdAt: '2026-01-01');
        $younger = $this->loan(principal: 50000, monthly: 40000, employee: $employee, createdAt: '2026-06-01');

        $period = $this->period();
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();

        // Everything available went to the older loan; nothing left for the younger.
        $olderPaid = (float) $older->fresh()->payments()->sum('amount');
        $youngerPaid = (float) $younger->fresh()->payments()->sum('amount');

        $this->assertGreaterThan(0.0, $olderPaid);
        $this->assertEquals(0.0, $youngerPaid);

        // And net pay was never driven negative.
        $this->assertGreaterThanOrEqual(0.0, (float) $payslip->net_pay);
    }

    /** Recomputing a period must not stack duplicate ledger rows. */
    public function test_recomputing_a_period_is_idempotent(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $loan = $this->loan(principal: 10000, monthly: 1500, employee: $employee);

        $period = $this->period();
        app(PayrollRunService::class)->run($period);
        app(PayrollRunService::class)->run($period);
        app(PayrollRunService::class)->run($period);

        $payrollPayments = $loan->fresh()->payments()
            ->where('type', EmployeeLoanPaymentType::Payroll)
            ->where('payroll_period_id', $period->id)
            ->get();

        $this->assertCount(1, $payrollPayments);
        $this->assertEquals(1500.0, (float) $payrollPayments->first()->amount);
        $this->assertEquals(8500.0, (float) $loan->fresh()->balance);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(1500.0, (float) $payslip->loan_deduction);
    }

    /**
     * A manual lump sum between runs shrinks what the next recompute withholds,
     * because the deduction is recalculated against the live balance.
     */
    public function test_manual_payment_reduces_what_the_next_run_withholds(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $loan = $this->loan(principal: 2000, monthly: 1500, employee: $employee);

        // Pay all but 300 off by hand.
        app(EmployeeLoanService::class)->recordManualPayment($loan, 1700);

        $period = $this->period();
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(300.0, (float) $payslip->loan_deduction);
        $this->assertEquals(0.0, (float) $loan->fresh()->balance);
        $this->assertEquals(EmployeeLoanStatus::Paid, $loan->fresh()->status);
    }

    // ---------------------------------------------------------------------

    private function employee(array $attributes = []): Employee
    {
        $user = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        return Employee::create($attributes + [
            'user_id' => $user->id,
            'staff_no' => 'EMP-'.$user->id,
            'full_name_en' => 'Loan Test Employee '.$user->id,
            'base_salary' => 20000,
            'legal_daily_hour_limit' => 8,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);
    }

    private function period(string $status = 'open'): PayrollPeriod
    {
        return PayrollPeriod::create([
            'name' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => $status,
        ]);
    }

    private function loan(
        float $principal,
        float $monthly,
        ?Employee $employee = null,
        ?string $createdAt = null,
    ): EmployeeLoan {
        $employee ??= $this->employee();

        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id,
            'reference' => 'LN-'.uniqid(),
            'principal_amount' => $principal,
            'monthly_amount' => $monthly,
            'duration_months' => (int) ceil($principal / max($monthly, 1)),
            'start_date' => '2026-01-01',
            'status' => EmployeeLoanStatus::Active,
        ]);

        if ($createdAt) {
            // created_at drives repayment order, so it has to be settable.
            $loan->forceFill(['created_at' => $createdAt])->save();
        }

        return $loan->fresh();
    }
}
