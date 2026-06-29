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
        'kircha_deduction' => 'Kircha',
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
        $payslips = $period->payslips()
            ->with('employee.position', 'employee.department')
            ->when($employeeIds, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->get()
            ->sortBy(fn ($p) => $p->employee?->full_name_en ?? '')
            ->values();

        return $payslips->map(function ($p, $i) {
            $employee = $p->employee;

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
                'kircha_deduction' => (float) $p->kircha_deduction,
                'other_deduction' => (float) $p->other_deduction,
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
