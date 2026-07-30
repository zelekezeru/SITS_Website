<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Splits a medical bill between institution and employee against the
 * cumulative, tiered medical allowance policy:
 *
 *  - Up to `fullCoverageLimit` (per employee, per policy year): 100% covered.
 *  - Between `fullCoverageLimit` and `maxCoverageLimit`: `coinsuranceRate`% covered,
 *    the rest borne by the employee.
 *  - Above `maxCoverageLimit`: 0% covered — entirely the employee's own expense.
 *
 * Coverage is cumulative: `priorReservedTotal` is the sum of covered_amount
 * already locked in by the employee's other approved/paid claims this policy
 * year, so a bill picks up wherever the running total left off rather than
 * re-opening the full-coverage tier on every claim.
 */
class MedicalAllowanceCalculator
{
    public function __construct(
        private readonly float $fullCoverageLimit,
        private readonly float $maxCoverageLimit,
        private readonly float $coinsuranceRate, // percent, e.g. 50.0 = 50%
    ) {}

    public static function fromSettings(): self
    {
        return new self(
            fullCoverageLimit: (float) Setting::get('medical_full_coverage_limit', 5000),
            maxCoverageLimit: (float) Setting::get('medical_max_coverage_limit', 10000),
            coinsuranceRate: (float) Setting::get('medical_coinsurance_rate', 50),
        );
    }

    /**
     * @return array{covered_amount: float, employee_amount: float}
     */
    public function split(float $priorReservedTotal, float $billAmount): array
    {
        $priorReservedTotal = max($priorReservedTotal, 0.0);
        $billAmount = max($billAmount, 0.0);

        $remainingFull = max($this->fullCoverageLimit - $priorReservedTotal, 0.0);
        $inFullTier = min($billAmount, $remainingFull);

        $afterFullTier = $billAmount - $inFullTier;
        $coinsuranceBase = max($priorReservedTotal, $this->fullCoverageLimit);
        $remainingCoinsurance = max($this->maxCoverageLimit - $coinsuranceBase, 0.0);
        $inCoinsuranceTier = min($afterFullTier, $remainingCoinsurance);

        $covered = round($inFullTier + $inCoinsuranceTier * ($this->coinsuranceRate / 100), 2);
        $covered = min($covered, $billAmount);
        $employeeAmount = round($billAmount - $covered, 2);

        return ['covered_amount' => $covered, 'employee_amount' => $employeeAmount];
    }
}
