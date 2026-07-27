<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;

// Find Birhanu
$employee = Employee::where('full_name_en', 'Birhanu Gelaye')->first();
if (!$employee) {
    echo "Employee not found\n";
    exit;
}

$period = PayrollPeriod::latest()->first();

$calculator = PayrollCalculator::fromSettings();

$result = $calculator->compute(
    $employee,
    null,
    \Carbon\Carbon::parse($period->end_date),
    \App\Models\PayrollComponentAssignment::where('employee_id', $employee->id)->with('component')->get(),
    \App\Models\PayrollComponent::statutory()->get()
);

echo "Birhanu's Payslip (Calculated):\n";
echo "Base Salary: " . $employee->base_salary . "\n";
echo "Transport Allowance: " . $result['transport_allowance'] . "\n";
echo "Housing Allowance: " . $result['housing_allowance'] . "\n";
echo "Cash Allowance: " . $result['cash_allowance'] . "\n";
echo "Gross Pay: " . $result['gross'] . "\n";
echo "Taxable Income: " . $result['taxable_income'] . "\n";
echo "Income Tax: " . $result['income_tax'] . "\n";
echo "Employee Pension: " . $result['employee_pension'] . "\n";
echo "Salary Advance: " . $result['salary_advance'] . "\n";
echo "Total Deductions: " . $result['total_deductions'] . "\n";
echo "Net Pay: " . $result['net_pay'] . "\n";
