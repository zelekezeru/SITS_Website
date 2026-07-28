<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Support\PayrollSheetPresenter;

/**
 * Computes period-over-period payroll variance metrics (Gross, Tax, Pension, Net Pay,
 * Headcount) and identifies individual employee variations > 5% before payroll submission.
 */
class PayrollVariancePresenter
{
    /**
     * @param array<int, array<string, mixed>>|null $currentRows already-built rows for
     *        $period; pass them in to avoid rebuilding the sheet a second time.
     */
    public static function analyze(PayrollPeriod $period, ?array $currentRows = null): array
    {
        $currentRows ??= PayrollSheetPresenter::rows($period);
        $currentTotals = PayrollSheetPresenter::totals($currentRows);

        // Find previous monthly period
        $previousPeriod = PayrollPeriod::monthly()
            ->where('start_date', '<', $period->start_date)
            ->orderByDesc('start_date')
            ->first();

        if (! $previousPeriod) {
            return [
                'has_previous' => false,
                'previous_period_name' => null,
                'summary' => [
                    'headcount' => ['current' => count($currentRows), 'previous' => 0, 'diff' => count($currentRows), 'pct' => 100.0],
                    'gross' => ['current' => $currentTotals['gross'] ?? 0.0, 'previous' => 0.0, 'diff' => $currentTotals['gross'] ?? 0.0, 'pct' => 100.0],
                    'income_tax' => ['current' => $currentTotals['income_tax'] ?? 0.0, 'previous' => 0.0, 'diff' => $currentTotals['income_tax'] ?? 0.0, 'pct' => 100.0],
                    'pension' => ['current' => ($currentTotals['employee_pension'] ?? 0.0) + ($currentTotals['employer_pension'] ?? 0.0), 'previous' => 0.0, 'diff' => ($currentTotals['employee_pension'] ?? 0.0) + ($currentTotals['employer_pension'] ?? 0.0), 'pct' => 100.0],
                    'net_pay' => ['current' => $currentTotals['net_pay'] ?? 0.0, 'previous' => 0.0, 'diff' => $currentTotals['net_pay'] ?? 0.0, 'pct' => 100.0],
                ],
                'variations' => [],
            ];
        }

        $previousRows = PayrollSheetPresenter::rows($previousPeriod);
        $previousTotals = PayrollSheetPresenter::totals($previousRows);

        $currNetTotal = $currentTotals['net_pay'] ?? 0.0;
        $prevNetTotal = $previousTotals['net_pay'] ?? 0.0;
        $currGrossTotal = $currentTotals['gross'] ?? 0.0;
        $prevGrossTotal = $previousTotals['gross'] ?? 0.0;
        $currTaxTotal = $currentTotals['income_tax'] ?? 0.0;
        $prevTaxTotal = $previousTotals['income_tax'] ?? 0.0;
        $currPensionTotal = ($currentTotals['employee_pension'] ?? 0.0) + ($currentTotals['employer_pension'] ?? 0.0);
        $prevPensionTotal = ($previousTotals['employee_pension'] ?? 0.0) + ($previousTotals['employer_pension'] ?? 0.0);

        $summary = [
            'headcount' => self::calcDelta(count($currentRows), count($previousRows)),
            'gross' => self::calcDelta($currGrossTotal, $prevGrossTotal),
            'income_tax' => self::calcDelta($currTaxTotal, $prevTaxTotal),
            'pension' => self::calcDelta($currPensionTotal, $prevPensionTotal),
            'net_pay' => self::calcDelta($currNetTotal, $prevNetTotal),
        ];

        // Map employees in current and previous periods by employee_id
        $currByEmp = collect($currentRows)->keyBy('employee_id');
        $prevByEmp = collect($previousRows)->keyBy('employee_id');

        $variations = [];

        foreach ($currByEmp as $empId => $curr) {
            $prev = $prevByEmp->get($empId);

            if (! $prev) {
                $variations[] = [
                    'employee_id' => $empId,
                    'name' => $curr['name'],
                    'staff_no' => $curr['staff_no'],
                    'type' => 'new_hire',
                    'note' => 'New addition to payroll',
                    'current_net' => $curr['net_pay'],
                    'previous_net' => 0.0,
                    'diff' => $curr['net_pay'],
                    'pct' => 100.0,
                ];
                continue;
            }

            $diff = round($curr['net_pay'] - $prev['net_pay'], 2);
            $prevNet = $prev['net_pay'];
            $pct = $prevNet > 0 ? round(($diff / $prevNet) * 100, 1) : 0.0;

            // Flag variations where net pay changes by > 5% or difference > 50 ETB
            if (abs($pct) >= 5.0 && abs($diff) >= 50.0) {
                $variations[] = [
                    'employee_id' => $empId,
                    'name' => $curr['name'],
                    'staff_no' => $curr['staff_no'],
                    'type' => $diff > 0 ? 'increase' : 'decrease',
                    'note' => sprintf('Net pay %s by %s%% (ETB %s)', $diff > 0 ? 'increased' : 'decreased', abs($pct), number_format(abs($diff), 2)),
                    'current_net' => $curr['net_pay'],
                    'previous_net' => $prev['net_pay'],
                    'diff' => $diff,
                    'pct' => $pct,
                ];
            }
        }

        foreach ($prevByEmp as $empId => $prev) {
            if (! $currByEmp->has($empId)) {
                $variations[] = [
                    'employee_id' => $empId,
                    'name' => $prev['name'],
                    'staff_no' => $prev['staff_no'],
                    'type' => 'removed',
                    'note' => 'Removed from current payroll',
                    'current_net' => 0.0,
                    'previous_net' => $prev['net_pay'],
                    'diff' => -$prev['net_pay'],
                    'pct' => -100.0,
                ];
            }
        }

        return [
            'has_previous' => true,
            'previous_period_name' => $previousPeriod->name,
            'summary' => $summary,
            'variations' => $variations,
        ];
    }

    private static function calcDelta(float|int $current, float|int $previous): array
    {
        $diff = round($current - $previous, 2);
        $pct = $previous > 0 ? round(($diff / $previous) * 100, 1) : ($current > 0 ? 100.0 : 0.0);

        return [
            'current' => $current,
            'previous' => $previous,
            'diff' => $diff,
            'pct' => $pct,
        ];
    }
}
