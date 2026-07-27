<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\PayrollCalculator;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    public function test_attendance_exempt_employee_has_no_absence_deduction(): void
    {
        $calculator = new PayrollCalculator([
            'working_days'    => 26,
            'ot_normal'       => 1.5,
            'ot_night'        => 1.5,
            'ot_rest'         => 2.0,
            'ot_holiday'      => 2.5,
            'transport_cap'   => 2200,
            'pension_pre_tax' => true,
        ]);

        $employee = new Employee([
            'base_salary'             => 26000,
            'legal_daily_hour_limit'  => 8,
            'attendance_exempt'       => true,
        ]);

        $attendance = new AttendanceRecord([
            'absent_days'    => 5,
            'permitted_days' => 0,
        ]);

        $result = $calculator->compute($employee, $attendance);

        $this->assertEquals(0.0, $result['absence_deduction']);
        $this->assertEquals(0, $result['unpermitted_days']);
    }

    public function test_part_time_and_contract_employees_are_statutory_exempt(): void
    {
        $pensionComponent = new \App\Models\PayrollComponent([
            'name' => 'Employee Pension',
            'kind' => \App\Enums\PayrollComponentKind::Statutory,
            'side' => \App\Enums\ComponentSide::Employee,
            'applies_to' => 'pension_members',
            'rate' => 7.0,
            'is_active' => true,
        ]);

        $pfComponent = new \App\Models\PayrollComponent([
            'name' => 'Provident Fund (Employee)',
            'kind' => \App\Enums\PayrollComponentKind::Statutory,
            'side' => \App\Enums\ComponentSide::Employee,
            'applies_to' => 'pf_members',
            'rate' => 5.0,
            'is_active' => true,
        ]);

        $fullTimeEmp = new Employee([
            'employment_type' => \App\Enums\EmploymentType::FullTime,
            'has_provident_fund' => false,
            'statutory_exempt' => false,
        ]);

        $partTimeEmp = new Employee([
            'employment_type' => \App\Enums\EmploymentType::PartTime,
            'has_provident_fund' => false,
            'statutory_exempt' => false,
        ]);

        $contractEmp = new Employee([
            'employment_type' => \App\Enums\EmploymentType::Contract,
            'has_provident_fund' => true,
            'statutory_exempt' => false,
        ]);

        $this->assertTrue($pensionComponent->appliesToEmployee($fullTimeEmp));
        $this->assertFalse($pensionComponent->appliesToEmployee($partTimeEmp));
        $this->assertFalse($pfComponent->appliesToEmployee($contractEmp));
    }

    /** An absent employee under the default policy loses one basic-salary day per day. */
    public function test_absence_defaults_to_one_basic_salary_day_per_absent_day(): void
    {
        $result = $this->computeAbsence([]);

        // 26,000 / 26 working days = 1,000 a day, 3 unpermitted days.
        $this->assertEquals(3, $result['unpermitted_days']);
        $this->assertEquals(3000.0, $result['absence_deduction']);
    }

    /** Grace days are forgiven before anything is charged. */
    public function test_absence_grace_days_are_forgiven_first(): void
    {
        $result = $this->computeAbsence(['absence_grace_days' => 2.0]);

        $this->assertEquals(1, $result['unpermitted_days']);
        $this->assertEquals(1000.0, $result['absence_deduction']);
    }

    /** The rate scales what a single absent day costs. */
    public function test_absence_rate_scales_the_deduction(): void
    {
        $result = $this->computeAbsence(['absence_rate' => 1.5]);

        $this->assertEquals(4500.0, $result['absence_deduction']);
    }

    /** Switching the policy off records the days but charges nothing. */
    public function test_absence_can_be_switched_off(): void
    {
        $result = $this->computeAbsence(['absence_enabled' => false]);

        $this->assertEquals(0, $result['unpermitted_days']);
        $this->assertEquals(0.0, $result['absence_deduction']);
    }

    /**
     * Absence is withheld post-tax: it lands in total deductions and reduces net
     * pay, but leaves taxable income (and therefore the tax itself) untouched.
     */
    public function test_absence_is_deducted_after_tax(): void
    {
        $without = $this->computeAbsence(['absence_enabled' => false]);
        $with = $this->computeAbsence([]);

        $this->assertEquals($without['taxable_income'], $with['taxable_income']);
        $this->assertEquals($without['income_tax'], $with['income_tax']);
        $this->assertEquals($without['gross'], $with['gross']);

        $this->assertEquals(
            round($without['total_deductions'] + 3000.0, 2),
            round($with['total_deductions'], 2),
        );
        $this->assertEquals(
            round($without['net_pay'] - 3000.0, 2),
            round($with['net_pay'], 2),
        );
    }

    /**
     * Runs a fixed 26,000 ETB employee with 3 unpermitted absent days through the
     * calculator, overriding only the policy keys under test.
     *
     * @param  array<string, mixed>  $policy
     * @return array<string, mixed>
     */
    private function computeAbsence(array $policy): array
    {
        $calculator = new PayrollCalculator($policy + ['working_days' => 26]);

        $employee = new Employee([
            'base_salary' => 26000,
            'legal_daily_hour_limit' => 8,
            'attendance_exempt' => false,
        ]);

        $attendance = new AttendanceRecord([
            'absent_days' => 4,
            'permitted_days' => 1,
        ]);

        return $calculator->compute($employee, $attendance);
    }
}
