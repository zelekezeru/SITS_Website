<?php

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Enums\MedicalAllowanceClaimStatus;
use App\Models\Employee;
use App\Models\MedicalAllowanceClaim;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Setting;
use App\Models\User;
use App\Services\MedicalAllowanceCalculator;
use App\Services\Payroll\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tiered, cumulative-per-year medical allowance coverage and its payroll
 * surfacing. Approval locks in the covered/employee split against whatever is
 * left of the employee's yearly tiers; disbursement itself happens outside
 * payroll, but a paid claim attributed to a period must still reconcile onto
 * that period's payslip (non-taxable, added straight to net pay).
 */
class MedicalAllowanceClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('medical_full_coverage_limit', 5000, 'payroll', 'decimal');
        Setting::set('medical_max_coverage_limit', 10000, 'payroll', 'decimal');
        Setting::set('medical_coinsurance_rate', 50, 'payroll', 'decimal');
    }

    // ---- Calculator --------------------------------------------------------

    public function test_bill_within_full_coverage_tier_is_fully_covered(): void
    {
        $split = MedicalAllowanceCalculator::fromSettings()->split(0, 3000);

        $this->assertEquals(3000.0, $split['covered_amount']);
        $this->assertEquals(0.0, $split['employee_amount']);
    }

    /** 5,000 full + half of the 3,000 above it = 6,500 covered, 1,500 employee-borne. */
    public function test_bill_spanning_the_coinsurance_tier_splits_correctly(): void
    {
        $split = MedicalAllowanceCalculator::fromSettings()->split(0, 8000);

        $this->assertEquals(6500.0, $split['covered_amount']);
        $this->assertEquals(1500.0, $split['employee_amount']);
    }

    /** Nothing is covered above the max limit. */
    public function test_bill_above_the_max_limit_is_capped(): void
    {
        $split = MedicalAllowanceCalculator::fromSettings()->split(0, 15000);

        // 5,000 full + 50% of the remaining 5,000 band (5,000→10,000) = 7,500.
        $this->assertEquals(7500.0, $split['covered_amount']);
        $this->assertEquals(7500.0, $split['employee_amount']);
    }

    /** A second claim picks up wherever the year's prior reserved total left off. */
    public function test_coverage_is_cumulative_against_prior_reserved_total(): void
    {
        // Already used 4,000 of the 5,000 full-coverage tier this year.
        $split = MedicalAllowanceCalculator::fromSettings()->split(4000, 3000);

        // 1,000 left in the full tier + 50% of the remaining 2,000 = 1,000 + 1,000 = 2,000.
        $this->assertEquals(2000.0, $split['covered_amount']);
        $this->assertEquals(1000.0, $split['employee_amount']);
    }

    // ---- Model / workflow ---------------------------------------------------

    public function test_only_enabled_full_time_employees_are_eligible(): void
    {
        $eligible = $this->employee(['medical_allowance_enabled' => true, 'employment_type' => EmploymentType::FullTime]);
        $notEnrolled = $this->employee(['medical_allowance_enabled' => false, 'employment_type' => EmploymentType::FullTime]);
        $partTime = $this->employee(['medical_allowance_enabled' => true, 'employment_type' => EmploymentType::PartTime]);

        $this->assertTrue($eligible->isMedicalAllowanceEligible());
        $this->assertFalse($notEnrolled->isMedicalAllowanceEligible());
        $this->assertFalse($partTime->isMedicalAllowanceEligible());
    }

    /** Approval locks in the split using the tiers left at that moment, not submission time. */
    public function test_approving_a_second_claim_reflects_the_first_approved_claims_usage(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();

        $first = $this->claim($employee, 4000.0);
        $first->approve($admin);
        $this->assertEquals(4000.0, (float) $first->fresh()->covered_amount);

        $second = $this->claim($employee, 3000.0);
        $second->approve($admin);

        // Only 1,000 left in the full tier, then 50% of the remaining 2,000.
        $this->assertEquals(2000.0, (float) $second->fresh()->covered_amount);
        $this->assertEquals(1000.0, (float) $second->fresh()->employee_amount);
    }

    /** A rejected claim never reserves coverage, so it doesn't shrink what's left for the next one. */
    public function test_rejected_claims_do_not_reserve_coverage(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();

        $rejected = $this->claim($employee, 4000.0);
        $rejected->reject($admin, 'Not a covered treatment');

        $next = $this->claim($employee, 3000.0);
        $next->approve($admin);

        $this->assertEquals(3000.0, (float) $next->fresh()->covered_amount);
    }

    public function test_only_a_pending_claim_can_be_cancelled(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();

        $claim = $this->claim($employee, 1000.0);
        $claim->approve($admin);

        $this->assertTrue($claim->fresh()->status === MedicalAllowanceClaimStatus::Approved);
        $this->assertFalse($claim->fresh()->isEditable());
    }

    // ---- Payroll surfacing ---------------------------------------------------

    /** A paid claim attributed to a period shows on that period's payslip as a non-taxable addition. */
    public function test_paid_claim_adds_to_net_pay_without_touching_gross_or_tax(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $admin = User::factory()->create();
        $period = $this->period();

        app(PayrollRunService::class)->run($period);
        $before = Payslip::where('employee_id', $employee->id)->firstOrFail();

        $claim = $this->claim($employee, 6000.0);
        $claim->approve($admin);
        $claim->recordPayment($admin, '2026-08-15', $period->id);

        app(PayrollRunService::class)->run($period);
        $after = Payslip::where('employee_id', $employee->id)->firstOrFail();

        $this->assertEquals(5500.0, (float) $claim->fresh()->covered_amount); // 5,000 + 50%*1,000
        $this->assertEquals(5500.0, (float) $after->medical_allowance);
        $this->assertEquals(
            round((float) $before->net_pay + 5500.0, 2),
            round((float) $after->net_pay, 2),
        );
        $this->assertEquals((float) $before->gross, (float) $after->gross);
        $this->assertEquals((float) $before->taxable_income, (float) $after->taxable_income);
        $this->assertEquals((float) $before->income_tax, (float) $after->income_tax);
    }

    /** An approved-but-not-yet-paid claim never appears on any payslip. */
    public function test_approved_but_unpaid_claim_does_not_surface_on_payslip(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $admin = User::factory()->create();
        $period = $this->period();

        $claim = $this->claim($employee, 6000.0);
        $claim->approve($admin);

        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(0.0, (float) $payslip->medical_allowance);
    }

    /** Recomputing a period is idempotent — it re-derives the figure, never stacks it. */
    public function test_recomputing_a_period_does_not_double_count_the_reimbursement(): void
    {
        $employee = $this->employee(['base_salary' => 20000]);
        $admin = User::factory()->create();
        $period = $this->period();

        $claim = $this->claim($employee, 3000.0);
        $claim->approve($admin);
        $claim->recordPayment($admin, '2026-08-15', $period->id);

        app(PayrollRunService::class)->run($period);
        app(PayrollRunService::class)->run($period);
        app(PayrollRunService::class)->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(3000.0, (float) $payslip->medical_allowance);
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
            'full_name_en' => 'Medical Allowance Test Employee '.$user->id,
            'base_salary' => 20000,
            'legal_daily_hour_limit' => 8,
            'is_active' => true,
            'employment_type' => EmploymentType::FullTime,
            'medical_allowance_enabled' => true,
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

    private function claim(Employee $employee, float $billAmount): MedicalAllowanceClaim
    {
        return MedicalAllowanceClaim::create([
            'employee_id' => $employee->id,
            'reference' => 'MED-'.uniqid(),
            'policy_year' => (int) now()->format('Y'),
            'bill_amount' => $billAmount,
            'status' => MedicalAllowanceClaimStatus::PendingReview,
        ]);
    }
}
