<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

/**
 * Core payroll "frames" mapped to the fixed SITS sheet columns. Seeded as
 * is_system so the Admin can rename/re-rate them but not delete them; new
 * allowance/deduction titles are added from the Payroll Setup screen and roll
 * into the Cash & Other / Other Deduction columns.
 */
class PayrollComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            // ── Allowances ──────────────────────────────────────────────
            ['name' => 'Transport Allowance', 'kind' => 'allowance', 'calc_type' => 'fixed', 'taxable' => true,  'exempt_capped' => true,  'sheet_column' => 'transport_allowance', 'sort_order' => 10],
            ['name' => 'Housing Allowance',   'kind' => 'allowance', 'calc_type' => 'fixed', 'taxable' => true,  'exempt_capped' => false, 'sheet_column' => 'housing_allowance',   'sort_order' => 11],
            ['name' => 'Mobile Allowance',    'kind' => 'allowance', 'calc_type' => 'fixed', 'taxable' => false, 'exempt_capped' => false, 'sheet_column' => 'mobile_allowance',    'sort_order' => 12],
            ['name' => 'Cash Allowance',      'kind' => 'allowance', 'calc_type' => 'fixed', 'taxable' => true,  'exempt_capped' => false, 'sheet_column' => 'cash_allowance',      'sort_order' => 13],

            // ── Deductions ──────────────────────────────────────────────
            ['name' => 'Salary Advance', 'kind' => 'deduction', 'calc_type' => 'fixed', 'sheet_column' => 'salary_advance',   'sort_order' => 20],
            ['name' => 'Kircha (Meat Share)', 'kind' => 'deduction', 'calc_type' => 'fixed', 'sheet_column' => 'kircha_deduction', 'sort_order' => 21],

            // ── Statutory (pension scheme) ──────────────────────────────
            ['name' => 'Pension (Employee 7%)',  'kind' => 'statutory', 'calc_type' => 'percent', 'rate' => 7,    'side' => 'employee', 'applies_to' => 'pension_members', 'sheet_column' => 'employee_pension', 'sort_order' => 30],
            ['name' => 'Pension (Employer 11%)', 'kind' => 'statutory', 'calc_type' => 'percent', 'rate' => 11,   'side' => 'employer', 'applies_to' => 'pension_members', 'sheet_column' => 'employer_pension', 'sort_order' => 31],
            // Employer "other" provident contribution for pension members (the 1.5% (O) column).
            ['name' => 'Provident (Employer 1.5%)', 'kind' => 'statutory', 'calc_type' => 'percent', 'rate' => 1.5, 'side' => 'employer', 'applies_to' => 'pension_members', 'sheet_column' => 'provident_fund_employer', 'sort_order' => 34],

            // ── Statutory (provident fund scheme) ───────────────────────
            ['name' => 'Provident Fund (Employee 5%)',    'kind' => 'statutory', 'calc_type' => 'percent', 'rate' => 5,    'side' => 'employee', 'applies_to' => 'pf_members', 'sheet_column' => 'provident_fund_employee', 'sort_order' => 32],
            ['name' => 'Provident Fund (Employer 12.5%)', 'kind' => 'statutory', 'calc_type' => 'percent', 'rate' => 12.5, 'side' => 'employer', 'applies_to' => 'pf_members', 'sheet_column' => 'provident_fund_employer', 'sort_order' => 33],
        ];

        foreach ($components as $c) {
            PayrollComponent::updateOrCreate(
                ['name' => $c['name']],
                array_merge([
                    'applies_to' => 'all',
                    'taxable' => true,
                    'exempt_capped' => false,
                    'is_active' => true,
                    'is_system' => true,
                ], $c)
            );
        }
    }
}
