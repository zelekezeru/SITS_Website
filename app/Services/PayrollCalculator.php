<?php

namespace App\Services;

use App\Enums\ComponentSide;
use App\Enums\PayrollComponentCalc;
use App\Enums\PayrollComponentKind;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Models\Setting;
use App\Models\TaxBracket;
use App\Support\Money;
use Carbon\Carbon;

/**
 * Ethiopian payroll computation (Proclamation 1395/2025).
 *
 * Earnings, deductions and statutory contributions come from admin-configured
 * PayrollComponents: allowance/deduction kinds attach to an employee via
 * PayrollComponentAssignment, statutory kinds (pension / provident fund) apply
 * globally by scheme membership. Each component carries the fixed payslip
 * "sheet_column" it rolls into; custom titles fall back to Cash & Other /
 * Other Deduction and are still itemised on the payslip lines.
 *
 * Tax base policy (unchanged): the pension-scheme employee contribution is
 * pre-tax; the Provident Fund employee share and all deductions are post-tax.
 * Results round so payslip lines always reconcile to the stored totals.
 */
class PayrollCalculator
{
    private const ALLOWANCE_COLUMNS = ['mobile_allowance', 'transport_allowance', 'housing_allowance', 'cash_allowance'];
    private const DEDUCTION_COLUMNS = ['salary_advance', 'other_deduction'];
    private const STATUTORY_COLUMNS = ['employee_pension', 'employer_pension', 'provident_fund_employee', 'provident_fund_employer'];

    /**
     * Policy defaults, applied to any key a caller leaves out. These reproduce the
     * statutory/legacy behaviour, so a partial config computes the same figures the
     * calculator produced before the setting existed.
     */
    private const DEFAULTS = [
        'working_days' => 26,
        'ot_normal' => 1.5,
        'ot_night' => 1.5,
        'ot_rest' => 2.0,
        'ot_holiday' => 2.5,
        'transport_cap' => 2200.0,
        'pension_pre_tax' => true,
        'absence_enabled' => true,
        'absence_basis' => 'base',
        'absence_rate' => 1.0,
        'absence_grace_days' => 0.0,
    ];

    /** @var array<string, mixed> */
    private array $config;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config + self::DEFAULTS;
    }

    public static function fromSettings(): self
    {
        return new self([
            'working_days' => (float) (Setting::get('working_days_per_month', 26) ?: 26),
            'ot_normal' => (float) Setting::get('ot_normal_multiplier', 1.5),
            'ot_night' => (float) Setting::get('ot_night_multiplier', 1.5),
            'ot_rest' => (float) Setting::get('ot_rest_multiplier', 2.0),
            'ot_holiday' => (float) Setting::get('ot_holiday_multiplier', 2.5),
            'transport_cap' => (float) Setting::get('transport_allowance_limit', 2200),
            // Whether the employee pension contribution reduces taxable income.
            // True = statutory (Ethiopian law); false = the SITS sheet convention.
            'pension_pre_tax' => filter_var(Setting::get('pension_pre_tax', false), FILTER_VALIDATE_BOOLEAN),
            // Unpaid-absence policy (always post-tax; see compute()).
            'absence_enabled' => filter_var(Setting::get('absence_deduction_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'absence_basis' => (string) (Setting::get('absence_deduction_basis', 'base') ?: 'base'),
            'absence_rate' => (float) (Setting::get('absence_deduction_rate', 1) ?: 1),
            'absence_grace_days' => (float) Setting::get('absence_grace_days', 0),
        ]);
    }

    /**
     * @param iterable<PayrollComponentAssignment> $assignments allowance/deduction entries already
     *        filtered to those that apply to this period.
     * @param iterable<PayrollComponent> $statutory active statutory components (pension / PF).
     */
    public function compute(
        Employee $employee,
        ?AttendanceRecord $attendance,
        ?Carbon $asOf = null,
        iterable $assignments = [],
        iterable $statutory = [],
    ): array {
        $base = (float) $employee->base_salary;
        $workingDays = $this->config['working_days'] > 0 ? $this->config['working_days'] : 26;
        $dailyHours = max((int) $employee->legal_daily_hour_limit, 1);
        $dailyRate = $base / $workingDays;
        $hourlyRate = $dailyRate / $dailyHours;

        // --- Overtime (tiered) -------------------------------------------
        $overtime = 0.0;
        if ($attendance) {
            $overtime =
                (float) $attendance->overtime_normal * $hourlyRate * $this->config['ot_normal']
                + (float) $attendance->ot_night * $hourlyRate * $this->config['ot_night']
                + (float) $attendance->ot_rest * $hourlyRate * $this->config['ot_rest']
                + (float) $attendance->ot_holiday * $hourlyRate * $this->config['ot_holiday'];
        }
        $overtime = round($overtime, 2);

        // --- Unpaid absence days ------------------------------------------
        // Employees marked as attendance_exempt are excluded from absence deductions,
        // as are the first `absence_grace_days` unpermitted days each month. The
        // ETB amount is worked out further down, once allowances are known — the
        // basis can be configured to include them.
        $unpermittedDays = ($attendance && ! $employee->attendance_exempt && $this->config['absence_enabled'])
            ? max(
                (int) $attendance->absent_days
                - (int) $attendance->permitted_days
                - $this->config['absence_grace_days'],
                0
            )
            : 0;

        // --- Columns + lines accumulators --------------------------------
        $cols = array_fill_keys(
            array_merge(self::ALLOWANCE_COLUMNS, self::DEDUCTION_COLUMNS, self::STATUTORY_COLUMNS),
            0.0
        );
        $lines = [['type' => 'earning', 'label' => 'Base Salary', 'amount' => round($base, 2)]];
        if ($overtime > 0) {
            $lines[] = ['type' => 'earning', 'label' => 'Overtime Pay', 'amount' => $overtime];
        }
        $taxableEarnings = $base + $overtime;

        // --- Allowance & deduction assignments ---------------------------
        foreach ($assignments as $assignment) {
            $component = $assignment->component;
            if (! $component || ! $component->is_active) {
                continue;
            }
            $amount = $this->componentAmount($component, $assignment, $base);
            if ($amount <= 0) {
                continue;
            }

            if ($component->kind === PayrollComponentKind::Allowance) {
                $column = in_array($component->sheet_column, self::ALLOWANCE_COLUMNS, true)
                    ? $component->sheet_column : 'cash_allowance';
                $cols[$column] += $amount;

                if ($component->taxable) {
                    if ($component->exempt_capped) {
                        $exempt = min($amount, $this->config['transport_cap'], $base * 0.25);
                        $taxableEarnings += max($amount - $exempt, 0);
                    } else {
                        $taxableEarnings += $amount;
                    }
                }

                $lines[] = ['type' => 'earning', 'label' => $component->name, 'amount' => $amount];
            } elseif ($component->kind === PayrollComponentKind::Deduction) {
                $column = in_array($component->sheet_column, self::DEDUCTION_COLUMNS, true)
                    ? $component->sheet_column : 'other_deduction';
                $cols[$column] += $amount;
                $lines[] = ['type' => 'deduction', 'label' => $component->name, 'amount' => $amount];
            }
        }

        // --- Statutory (pension / provident fund) ------------------------
        $statutoryEmployeePreTax = 0.0;   // reduces the tax base
        $statutoryEmployeePostTax = 0.0;  // deducted from net only
        // SITS sheet convention: ALL employer statutory contributions (Pension & PF) are
        // added to BOTH gross and total deductions so they cancel in net (presentational gross-up).
        $employerContrib = 0.0;
        foreach ($statutory as $component) {
            if (! $component->is_active || $component->kind !== PayrollComponentKind::Statutory) {
                continue;
            }
            if (! $component->appliesToEmployee($employee)) {
                continue;
            }

            $amount = round($base * (float) $component->rate / 100, 2);
            if ($amount <= 0) {
                continue;
            }

            $column = in_array($component->sheet_column, self::STATUTORY_COLUMNS, true) ? $component->sheet_column : null;
            if ($column) {
                $cols[$column] += $amount;
            }

            if ($component->side === ComponentSide::Employee) {
                // Pension-scheme employee contribution is pre-tax when the policy says
                // so (statutory default); PF is always post-tax.
                $preTax = $component->applies_to === 'pension_members'
                    && ($this->config['pension_pre_tax'] ?? true);
                if ($preTax) {
                    $statutoryEmployeePreTax += $amount;
                } else {
                    $statutoryEmployeePostTax += $amount;
                }
                $lines[] = ['type' => 'deduction', 'label' => $component->name, 'amount' => $amount];
            } else {
                // Employer side: added to BOTH gross pay and total deductions
                // so they cancel out in the net pay.
                $employerContrib += $amount;
                $lines[] = ['type' => 'deduction', 'label' => $component->name, 'amount' => $amount];
                // And to earnings so the payslip lines balance
                $lines[] = ['type' => 'earning', 'label' => $component->name, 'amount' => $amount];
            }
        }

        // --- Gross, tax, totals ------------------------------------------
        $earningsTotal = Money::round(
            $overtime + $cols['mobile_allowance'] + $cols['transport_allowance']
            + $cols['housing_allowance'] + $cols['cash_allowance']
        );
        // Gross pay includes the employer contributions (which are later deducted).
        $gross = Money::round($base + $earningsTotal + $employerContrib);

        // --- Unpaid absence amount (POST-TAX) -----------------------------
        // Withheld from taxed pay: it is added to total deductions below and is
        // deliberately kept out of $taxable — an absent day costs the employee the
        // net, not the gross, of that day. The daily rate is basic-salary based by
        // default; `absence_deduction_basis = gross` includes allowances, and
        // `absence_deduction_rate` scales how many days' pay one absent day costs.
        $absenceBasis = $this->config['absence_basis'] === 'gross'
            ? $base + $cols['mobile_allowance'] + $cols['transport_allowance']
                + $cols['housing_allowance'] + $cols['cash_allowance']
            : $base;
        $absenceDeduction = round(
            $unpermittedDays * ($absenceBasis / $workingDays) * $this->config['absence_rate'],
            2
        );

        $taxable = Money::round($taxableEarnings - $statutoryEmployeePreTax);
        $incomeTax = $this->incomeTax($taxable, $asOf);

        $deductionsTotal = $cols['salary_advance'] + $cols['other_deduction'];
        $totalDeductions = Money::round(
            $incomeTax + $statutoryEmployeePreTax + $statutoryEmployeePostTax
            + $absenceDeduction + $deductionsTotal + $employerContrib
        );
        $netPay = Money::round($gross - $totalDeductions);

        if ($absenceDeduction > 0) {
            $lines[] = ['type' => 'deduction', 'label' => "Unpaid Absence ({$unpermittedDays} day(s))", 'amount' => $absenceDeduction];
        }
        $lines[] = ['type' => 'deduction', 'label' => 'Personal Income Tax (PIT)', 'amount' => $incomeTax];

        return array_merge([
            'working_days' => $workingDays,
            'gross' => $gross,
            'overtime' => $overtime,
            'taxable_income' => $taxable,
            'income_tax' => $incomeTax,
            'absence_deduction' => $absenceDeduction,
            'unpermitted_days' => $unpermittedDays,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'lines' => $lines,
        ], array_map(fn ($v) => round($v, 2), $cols));
    }

    /** Resolve an assignment's ETB amount: fixed amount or a percentage of base. */
    private function componentAmount(PayrollComponent $component, PayrollComponentAssignment $assignment, float $base): float
    {
        if ($component->calc_type === PayrollComponentCalc::Percent) {
            return round($base * (float) $component->rate / 100, 2);
        }

        return round((float) $assignment->amount, 2);
    }

    /** Ethiopian PIT via the quick-deduction method: tax = income·rate − deduction. */
    public function incomeTax(float $taxable, ?Carbon $asOf = null): float
    {
        $bracket = $this->bracketFor($taxable, $asOf);

        if (! $bracket) {
            return 0.0;
        }

        $tax = $taxable * ((float) $bracket->rate / 100) - (float) $bracket->deduction;

        return round(max($tax, 0), 2);
    }

    /** The active, effective-dated PIT band a taxable income falls into. */
    public function bracketFor(float $income, ?Carbon $asOf = null): ?TaxBracket
    {
        return TaxBracket::query()
            ->where('is_active', true)
            ->when($asOf, fn ($q) => $q->where(fn ($w) => $w->whereNull('effective_from')->orWhere('effective_from', '<=', $asOf)))
            ->where('min_income', '<=', $income)
            ->where(fn ($q) => $q->whereNull('max_income')->orWhere('max_income', '>=', $income))
            ->orderByDesc('min_income')
            ->first();
    }
}
