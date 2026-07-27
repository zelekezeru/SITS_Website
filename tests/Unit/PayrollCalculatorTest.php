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
}
