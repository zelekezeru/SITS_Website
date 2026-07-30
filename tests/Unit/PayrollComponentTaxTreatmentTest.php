<?php

namespace Tests\Unit;

use App\Enums\ComponentSide;
use App\Enums\EmploymentType;
use App\Enums\PayrollComponentCalc;
use App\Enums\PayrollComponentKind;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Services\PayrollCalculator;
use Tests\TestCase;

/**
 * Covers the tax treatment that separates allowances from deductions:
 * allowances can move the tax base, deductions never can.
 *
 * These assert on `taxable_income` rather than `income_tax`. PIT brackets are
 * looked up from the database, which a unit test has no access to, so the tax
 * figure is 0 here regardless of the base. The bracket arithmetic itself is
 * exercised by the Feature tests, which run against seeded brackets.
 */
class PayrollComponentTaxTreatmentTest extends TestCase
{
    private const BASE = 26000.0;

    /** A plain taxable allowance lands in gross and raises the tax base in full. */
    public function test_taxable_allowance_raises_the_tax_base(): void
    {
        $baseline = $this->compute([]);
        $with = $this->compute([
            $this->allowance(2000, taxable: true, column: 'housing_allowance'),
        ]);

        $this->assertEquals(2000.0, $with['housing_allowance']);
        $this->assertEquals($baseline['taxable_income'] + 2000.0, $with['taxable_income']);
    }

    /** A non-taxable allowance is paid but left out of the tax base entirely. */
    public function test_non_taxable_allowance_does_not_touch_the_tax_base(): void
    {
        $baseline = $this->compute([]);
        $with = $this->compute([
            $this->allowance(2000, taxable: false, column: 'housing_allowance'),
        ]);

        $this->assertEquals(2000.0, $with['housing_allowance']);
        $this->assertEquals($baseline['taxable_income'], $with['taxable_income']);
        $this->assertEquals($baseline['income_tax'], $with['income_tax']);
        $this->assertEquals($baseline['gross'] + 2000.0, $with['gross']);
    }

    /**
     * The capped exemption is the LOWER of the configured cap and 25% of base.
     * Here 25% of 26,000 = 6,500, so the 2,200 cap binds and only the excess
     * above 2,200 is taxed.
     */
    public function test_capped_allowance_is_exempt_up_to_the_cap_when_the_cap_is_lower(): void
    {
        $baseline = $this->compute([]);
        $with = $this->compute([
            $this->allowance(3000, taxable: true, column: 'transport_allowance', capped: true),
        ]);

        // 3,000 paid, 2,200 exempt, 800 taxable.
        $this->assertEquals(3000.0, $with['transport_allowance']);
        $this->assertEquals($baseline['taxable_income'] + 800.0, $with['taxable_income']);
    }

    /**
     * When 25% of base is the lower figure it binds instead. On a 4,000 base,
     * 25% = 1,000, well under the 2,200 cap.
     */
    public function test_capped_allowance_is_exempt_up_to_25_percent_of_base_when_that_is_lower(): void
    {
        $baseline = $this->compute([], base: 4000.0);
        $with = $this->compute([
            $this->allowance(3000, taxable: true, column: 'transport_allowance', capped: true),
        ], base: 4000.0);

        // 3,000 paid, 1,000 exempt (25% of 4,000), 2,000 taxable.
        $this->assertEquals($baseline['taxable_income'] + 2000.0, $with['taxable_income']);
    }

    /** A voluntary deduction is withheld post-tax: the tax base is untouched. */
    public function test_voluntary_deduction_is_withheld_post_tax(): void
    {
        $baseline = $this->compute([]);
        $with = $this->compute([
            $this->deduction(1500, column: 'salary_advance'),
        ]);

        $this->assertEquals(1500.0, $with['salary_advance']);
        $this->assertEquals($baseline['taxable_income'], $with['taxable_income']);
        $this->assertEquals($baseline['income_tax'], $with['income_tax']);
        $this->assertEquals($baseline['gross'], $with['gross']);
        $this->assertEquals($baseline['total_deductions'] + 1500.0, $with['total_deductions']);
        $this->assertEquals($baseline['net_pay'] - 1500.0, $with['net_pay']);
    }

    /** A deduction with an unrecognised sheet column falls into other_deduction. */
    public function test_unmapped_deduction_falls_into_other_deduction(): void
    {
        $result = $this->compute([
            $this->deduction(600, column: 'not_a_real_column'),
        ]);

        $this->assertEquals(600.0, $result['other_deduction']);
        $this->assertEquals(0.0, $result['salary_advance']);
    }

    /** An allowance with an unrecognised sheet column falls into cash_allowance. */
    public function test_unmapped_allowance_falls_into_cash_allowance(): void
    {
        $result = $this->compute([
            $this->allowance(700, taxable: false, column: 'not_a_real_column'),
        ]);

        $this->assertEquals(700.0, $result['cash_allowance']);
    }

    /** Percent components price off base salary, ignoring the assignment amount. */
    public function test_percent_component_is_a_percentage_of_base(): void
    {
        $component = new PayrollComponent([
            'name' => 'Housing (10%)',
            'kind' => PayrollComponentKind::Allowance,
            'calc_type' => PayrollComponentCalc::Percent,
            'rate' => 10.0,
            'sheet_column' => 'housing_allowance',
            'taxable' => false,
            'is_active' => true,
        ]);
        $assignment = new PayrollComponentAssignment(['amount' => 999999]);
        $assignment->setRelation('component', $component);

        $result = $this->compute([$assignment]);

        $this->assertEquals(2600.0, $result['housing_allowance']);
    }

    /** The core identity: net is always gross minus every deduction. */
    public function test_net_pay_is_gross_minus_total_deductions(): void
    {
        $result = $this->compute([
            $this->allowance(3000, taxable: true, column: 'transport_allowance', capped: true),
            $this->allowance(1200, taxable: false, column: 'mobile_allowance'),
            $this->deduction(1500, column: 'salary_advance'),
            $this->deduction(400, column: 'kircha_deduction'),
        ]);

        $this->assertEquals(
            round($result['gross'] - $result['total_deductions'], 2),
            round($result['net_pay'], 2),
        );
    }

    /**
     * The employer statutory contribution is grossed up into both gross and
     * total deductions, so it cancels out and never costs the employee.
     */
    public function test_employer_statutory_contribution_cancels_out_in_net(): void
    {
        $employerPension = new PayrollComponent([
            'name' => 'Employer Pension',
            'kind' => PayrollComponentKind::Statutory,
            'side' => ComponentSide::Employer,
            'applies_to' => 'all',
            'rate' => 11.0,
            'sheet_column' => 'employer_pension',
            'is_active' => true,
        ]);

        $without = $this->compute([]);
        $with = $this->compute([], statutory: [$employerPension]);

        $contrib = round(self::BASE * 0.11, 2);

        $this->assertEquals($contrib, $with['employer_pension']);
        $this->assertEquals($without['gross'] + $contrib, $with['gross']);
        $this->assertEquals($without['total_deductions'] + $contrib, $with['total_deductions']);
        // The whole point: net pay is identical.
        $this->assertEquals($without['net_pay'], $with['net_pay']);
    }

    // ---------------------------------------------------------------------

    /**
     * @param  iterable<PayrollComponentAssignment>  $assignments
     * @param  iterable<PayrollComponent>  $statutory
     * @return array<string, mixed>
     */
    private function compute(iterable $assignments, iterable $statutory = [], float $base = self::BASE): array
    {
        $calculator = new PayrollCalculator([
            'working_days' => 26,
            'transport_cap' => 2200,
            'pension_pre_tax' => true,
            'absence_enabled' => false,
        ]);

        $employee = new Employee([
            'base_salary' => $base,
            'legal_daily_hour_limit' => 8,
            'attendance_exempt' => false,
            'employment_type' => EmploymentType::FullTime,
            'has_provident_fund' => false,
            'statutory_exempt' => false,
        ]);

        return $calculator->compute($employee, null, null, $assignments, $statutory);
    }

    private function allowance(float $amount, bool $taxable, string $column, bool $capped = false): PayrollComponentAssignment
    {
        return $this->assign([
            'name' => 'Test Allowance',
            'kind' => PayrollComponentKind::Allowance,
            'calc_type' => PayrollComponentCalc::Fixed,
            'sheet_column' => $column,
            'taxable' => $taxable,
            'exempt_capped' => $capped,
        ], $amount);
    }

    private function deduction(float $amount, string $column): PayrollComponentAssignment
    {
        return $this->assign([
            'name' => 'Test Deduction',
            'kind' => PayrollComponentKind::Deduction,
            'calc_type' => PayrollComponentCalc::Fixed,
            'sheet_column' => $column,
        ], $amount);
    }

    /** @param  array<string, mixed>  $attributes */
    private function assign(array $attributes, float $amount): PayrollComponentAssignment
    {
        $component = new PayrollComponent($attributes + ['is_active' => true]);
        $assignment = new PayrollComponentAssignment(['amount' => $amount]);
        $assignment->setRelation('component', $component);

        return $assignment;
    }
}
