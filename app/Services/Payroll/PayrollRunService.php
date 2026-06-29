<?php

namespace App\Services\Payroll;

use App\Enums\AttendancePermissionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates/refreshes every active employee's payslip for a payroll period from
 * salaries, attendance, the scheduled payroll-component assignments and the
 * approved attendance permissions. Used by both the admin run and the
 * Finance/Operations "prepare/recompute" action so the stored sheet columns
 * always reconcile to the line items.
 */
class PayrollRunService
{
    /** @return int number of payslips generated */
    public function run(PayrollPeriod $period, ?User $preparedBy = null): int
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->with([
                'department.campus',
                'componentAssignments.component',
                'componentAssignments.startPeriod',
                'componentAssignments.endPeriod',
                'attendancePermissions' => fn ($q) => $q
                    ->where('payroll_period_id', $period->id)
                    ->where('status', AttendancePermissionStatus::Approved),
            ])
            ->get();

        // Statutory components (pension / provident fund) are shared across everyone.
        $statutory = PayrollComponent::active()->statutory()->get();

        $calculator = PayrollCalculator::fromSettings();
        $asOf = $period->end_date ? Carbon::parse($period->end_date) : null;

        DB::transaction(function () use ($period, $employees, $statutory, $calculator, $asOf, $preparedBy) {
            foreach ($employees as $employee) {
                // Fold approved permission days into permitted_days before computing.
                $permittedDays = (int) $employee->attendancePermissions->sum('days');

                $attendance = AttendanceRecord::firstOrCreate(
                    ['employee_id' => $employee->id, 'payroll_period_id' => $period->id],
                    ['work_hours' => 0, 'status' => AttendanceStatus::Verified->value],
                );
                if ($permittedDays > 0 && $permittedDays !== (int) $attendance->permitted_days) {
                    $attendance->update(['permitted_days' => $permittedDays]);
                }

                // Only the assignments whose schedule covers this period apply.
                $assignments = $employee->componentAssignments->filter->appliesTo($period);

                $result = $calculator->compute($employee, $attendance, $asOf, $assignments, $statutory);

                $payslip = Payslip::updateOrCreate(
                    ['employee_id' => $employee->id, 'payroll_period_id' => $period->id],
                    [
                        'grade' => $employee->grade,
                        'campus' => $employee->campusName(),
                        'working_days' => $result['working_days'],
                        'gross' => $result['gross'],
                        'overtime' => $result['overtime'],
                        'mobile_allowance' => $result['mobile_allowance'],
                        'transport_allowance' => $result['transport_allowance'],
                        'housing_allowance' => $result['housing_allowance'],
                        'cash_allowance' => $result['cash_allowance'],
                        'taxable_income' => $result['taxable_income'],
                        'income_tax' => $result['income_tax'],
                        'employee_pension' => $result['employee_pension'],
                        'employer_pension' => $result['employer_pension'],
                        'provident_fund_employee' => $result['provident_fund_employee'],
                        'provident_fund_employer' => $result['provident_fund_employer'],
                        'salary_advance' => $result['salary_advance'],
                        'kircha_deduction' => $result['kircha_deduction'],
                        'other_deduction' => $result['other_deduction'],
                        'total_deductions' => $result['total_deductions'],
                        'net_pay' => $result['net_pay'],
                        'status' => 'draft',
                    ],
                );

                $payslip->lines()->delete();
                foreach ($result['lines'] as $line) {
                    $payslip->lines()->create($line);
                }
            }

            $period->update([
                'status' => 'processing',
                'prepared_by' => $preparedBy?->id ?? $period->prepared_by,
                'prepared_at' => now(),
            ]);
        });

        return $employees->count();
    }
}
