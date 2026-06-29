<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Models\PayrollPeriod;
use Illuminate\Database\Seeder;

/**
 * Loads the real figures from the March-2026 SITS payroll sheet onto the staff
 * that exist in the roster: base salary, grade, statutory scheme (pension /
 * provident fund / none), the standing allowances (transport, housing, cash)
 * and the one-time salary advances. Lets the payroll export be produced and
 * compared against the acceptable sheet.
 *
 * Run after UserRoleSeeder (employees), PayrollComponentSeeder (components) and
 * PeriodSeeder (periods).
 */
class PayrollSheetDataSeeder extends Seeder
{
    public function run(): void
    {
        // scheme: pf = provident fund, pension = public pension, none = exempt.
        $staff = [
            ['name' => 'Endale Sebsebe Mekonnen',  'grade' => 'G13-L5', 'salary' => 54934.20, 'scheme' => 'pf'],
            ['name' => 'Meskerem Aseffa',          'grade' => 'G6-L10', 'salary' => 22838.10, 'scheme' => 'pension', 'allowances' => ['Transport Allowance' => 500, 'Housing Allowance' => 2000]],
            ['name' => 'Birhanu Gelaye',           'grade' => 'G8-L5',  'salary' => 22996.90, 'scheme' => 'pension', 'allowances' => ['Transport Allowance' => 600, 'Cash Allowance' => 1200, 'Housing Allowance' => 2000], 'advance' => 2520.34],
            ['name' => 'Yilma Gezmu Mengesha',     'grade' => 'G9-L2',  'salary' => 25103.47, 'scheme' => 'none',    'allowances' => ['Cash Allowance' => 1000]],
            ['name' => 'Mesganu Petros',           'grade' => 'G7-L10', 'salary' => 22589.07, 'scheme' => 'pension', 'allowances' => ['Transport Allowance' => 600, 'Housing Allowance' => 2000], 'advance' => 2067.29],
            ['name' => 'Zeleke Abisso',            'grade' => 'G7-L4',  'salary' => 15221.97, 'scheme' => 'none',    'advance' => 5073.99],
            ['name' => 'Tamiru Lijalem',           'grade' => 'G8-L6',  'salary' => 24491.70, 'scheme' => 'pension', 'allowances' => ['Housing Allowance' => 2000]],
            ['name' => 'Zerubbabel Zeleke',        'grade' => null,     'salary' => 15000.00, 'scheme' => 'none'],
            ['name' => 'Alte Agegnew Tadese',      'grade' => null,     'salary' => 11700.00, 'scheme' => 'none',    'advance' => 2388.88],
            ['name' => 'Pastor Zekariyas Chinasho','grade' => 'G7-L6',  'salary' => 20000.00, 'scheme' => 'pension', 'allowances' => ['Cash Allowance' => 2200]],
            ['name' => 'Misale Getu Ayalew',       'grade' => null,     'salary' => 20000.00, 'scheme' => 'none'],
            ['name' => 'Abenezer Ayalew Mekonnen', 'grade' => null,     'salary' => 17000.00, 'scheme' => 'none'],
        ];

        $components = PayrollComponent::whereIn('kind', ['allowance', 'deduction'])->get()->keyBy('name');
        $marchPeriod = PayrollPeriod::where('name', 'March 2026')->first();

        foreach ($staff as $row) {
            $employee = Employee::where('full_name_en', $row['name'])->first();
            if (! $employee) {
                continue;
            }

            $employee->update([
                'base_salary' => $row['salary'],
                'grade' => $row['grade'],
                'has_provident_fund' => $row['scheme'] === 'pf',
                'statutory_exempt' => $row['scheme'] === 'none',
            ]);

            // Standing allowances — recurring monthly.
            foreach ($row['allowances'] ?? [] as $componentName => $amount) {
                if ($component = $components->get($componentName)) {
                    PayrollComponentAssignment::updateOrCreate(
                        ['employee_id' => $employee->id, 'payroll_component_id' => $component->id, 'schedule_type' => 'monthly'],
                        ['amount' => $amount, 'is_active' => true],
                    );
                }
            }

            // One-time salary advance for the March period.
            if (! empty($row['advance']) && $marchPeriod && ($advance = $components->get('Salary Advance'))) {
                PayrollComponentAssignment::updateOrCreate(
                    ['employee_id' => $employee->id, 'payroll_component_id' => $advance->id, 'schedule_type' => 'one_time', 'start_period_id' => $marchPeriod->id],
                    ['amount' => $row['advance'], 'end_period_id' => $marchPeriod->id, 'is_active' => true],
                );
            }
        }
    }
}
