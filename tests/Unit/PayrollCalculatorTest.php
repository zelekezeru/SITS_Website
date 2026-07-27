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
}
