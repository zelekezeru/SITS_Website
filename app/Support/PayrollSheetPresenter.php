<?php

namespace App\Support;

use App\Models\PayrollPeriod;
use Illuminate\Support\Collection;

/**
 * Shapes a payroll period's payslips into the SITS monthly sheet — the per-employee
 * rows, the numeric column set and the totals footer. Single source for the
 * on-screen summary (Vue), the PDF replica and the Excel export.
 */
class PayrollSheetPresenter
{
    /** Numeric columns that make up the sheet and roll up into the totals row. */
    public const COLUMNS = [
        'base_salary' => 'Basic Salary',
        'absent_days' => 'Absent Days',
        'absence_deduction' => 'Absence Ded.',
        'overtime' => 'Overtime',
        'mobile_allowance' => 'Mobile Allow.',
        'transport_allowance' => 'Transport Allow.',
        'housing_allowance' => 'Housing Allow.',
        'cash_allowance' => 'Cash & Other',
        'gross' => 'Gross Salary',
        'taxable_income' => 'Taxable Income',
        'income_tax' => 'Income Tax',
        'employee_pension' => 'Pension 7%',
        'employer_pension' => 'Pension 11%',
        'provident_fund_employee' => 'PF 5%',
        'provident_fund_employer' => 'PF 12.5%',
        'salary_advance' => 'Salary Advance',
        'other_deduction' => 'Other Ded.',
        'total_deductions' => 'Total Deductions',
        'net_pay' => 'Net Pay',
    ];

    /**
     * @param array<int> $employeeIds optionally restrict to these employees
     * @return array<int, array<string, mixed>>
     */
    public static function rows(PayrollPeriod $period, array $employeeIds = []): array
    {
        $attendanceMap = \App\Models\AttendanceRecord::query()
            ->where('payroll_period_id', $period->id)
            ->when($employeeIds, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->get()
            ->keyBy('employee_id');

        $payslips = $period->payslips()
            ->with(['employee.position', 'employee.department', 'lines'])
            ->when($employeeIds, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->get()
            ->sortBy(fn ($p) => $p->employee?->full_name_en ?? '')
            ->values();

        return $payslips->map(function ($p, $i) use ($attendanceMap) {
            $employee = $p->employee;
            $att = $attendanceMap->get($p->employee_id);

            // Prefer the days the run actually charged — stored on the payslip, so
            // it always matches the absence amount beside it. Payslips generated
            // before that column existed fall back to the live attendance figure.
            $absentDays = $p->absent_days !== null
                ? (int) $p->absent_days
                : (($att && ! ($employee?->attendance_exempt))
                    ? max((int) $att->absent_days - (int) $att->permitted_days, 0)
                    : 0);

            $absenceLine = $p->lines->first(fn ($l) => str_starts_with($l->label, 'Unpaid Absence'));
            $absenceDeduction = $absenceLine ? (float) $absenceLine->amount : 0.0;

            return [
                'no' => $i + 1,
                'payslip_id' => $p->id,
                'employee_id' => $p->employee_id,
                'name' => $employee?->full_name_en ?? '—',
                'staff_no' => $employee?->staff_no,
                'grade' => $p->grade ?: $employee?->grade,
                'campus' => $p->campus,
                'position' => $employee?->position?->title_en,
                'department' => $employee?->department?->name_en,
                'has_provident_fund' => (bool) ($employee?->has_provident_fund),
                'working_days' => (float) $p->working_days,
                'absent_days' => $absentDays,
                'absence_deduction' => $absenceDeduction,
                'base_salary' => (float) ($employee?->base_salary ?? 0),
                'overtime' => (float) $p->overtime,
                'mobile_allowance' => (float) $p->mobile_allowance,
                'transport_allowance' => (float) $p->transport_allowance,
                'housing_allowance' => (float) $p->housing_allowance,
                'cash_allowance' => (float) $p->cash_allowance,
                'gross' => (float) $p->gross,
                'taxable_income' => (float) $p->taxable_income,
                'income_tax' => (float) $p->income_tax,
                'employee_pension' => (float) $p->employee_pension,
                'employer_pension' => (float) $p->employer_pension,
                'provident_fund_employee' => (float) $p->provident_fund_employee,
                'provident_fund_employer' => (float) $p->provident_fund_employer,
                'salary_advance' => (float) $p->salary_advance,
                'other_deduction' => (float) $p->other_deduction + (float) $p->kircha_deduction,
                'total_deductions' => (float) $p->total_deductions,
                'net_pay' => (float) $p->net_pay,
            ];
        })->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, float>
     */
    public static function totals(array $rows): array
    {
        $totals = [];
        foreach (array_keys(self::COLUMNS) as $key) {
            $totals[$key] = round(array_sum(array_column($rows, $key)), 2);
        }

        return $totals;
    }

    /**
     * Returns only the columns from COLUMNS where at least one row has a non-zero value,
     * so the payroll table, PDF and Excel hide empty deduction/allowance columns.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, string>
     */
    public static function activeColumns(array $rows): array
    {
        if (empty($rows)) {
            return self::COLUMNS;
        }

        // Always keep these structural columns visible regardless of values.
        $alwaysShow = ['base_salary', 'gross', 'taxable_income', 'total_deductions', 'net_pay'];

        return array_filter(self::COLUMNS, function (string $label, string $key) use ($rows, $alwaysShow) {
            if (in_array($key, $alwaysShow, true)) {
                return true;
            }

            // Show the column if any row has a non-zero value.
            foreach ($rows as $row) {
                if (isset($row[$key]) && (float) $row[$key] != 0.0) {
                    return true;
                }
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Active component assignments across all employees, keyed by employee id,
     * with a flag for whether each applies to this specific period.
     */
    public static function assignmentsByEmployee(PayrollPeriod $period): Collection
    {
        return \App\Models\PayrollComponentAssignment::query()
            ->where('is_active', true)
            ->with(['component', 'startPeriod', 'endPeriod'])
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'employee_id' => $a->employee_id,
                'component' => $a->component?->name,
                'kind' => $a->component?->kind->value,
                'is_earning' => $a->component?->kind === \App\Enums\PayrollComponentKind::Allowance,
                'amount' => (float) $a->amount,
                'schedule_type' => $a->schedule_type->value,
                'schedule_label' => $a->schedule_type->label(),
                'start_period' => $a->startPeriod?->name,
                'end_period' => $a->endPeriod?->name,
                'applies_now' => $a->appliesTo($period),
            ])
            ->groupBy('employee_id');
    }
}
