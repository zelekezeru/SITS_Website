<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Payroll\PayrollVariancePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('manage payroll', 'web');
        Permission::findOrCreate('export payroll', 'web');
    }

    public function test_finance_user_can_export_cbe_bank_file(): void
    {
        $user = User::factory()->create(['is_approved' => true, 'is_active' => true, 'password_changed' => true]);
        $user->givePermissionTo(['manage payroll', 'export payroll']);

        $empUser = User::factory()->create(['name' => 'Abebe Bikila', 'is_approved' => true, 'is_active' => true, 'password_changed' => true]);
        $employee = Employee::create([
            'user_id' => $empUser->id,
            'staff_no' => 'EMP-1001',
            'full_name_en' => 'Abebe Bikila',
            'base_salary' => 10000,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'processing',
        ]);

        Payslip::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'working_days' => 26,
            'gross' => 10000,
            'net_pay' => 7500,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('finance.payroll.export.cbe', $period));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('ACCOUNT_NUMBER,AMOUNT,BENEFICIARY_NAME,STAFF_NO,NARRATION', $response->streamedContent());
        $this->assertStringContainsString('7500.00', $response->streamedContent());
        $this->assertStringContainsString('Abebe Bikila', $response->streamedContent());
        $this->assertStringContainsString('EMP-1001', $response->streamedContent());
    }

    public function test_tax_and_pension_schedules_can_be_exported(): void
    {
        $user = User::factory()->create(['is_approved' => true, 'is_active' => true, 'password_changed' => true]);
        $user->givePermissionTo(['manage payroll', 'export payroll']);

        $empUser = User::factory()->create(['name' => 'Teshome Lemma', 'is_approved' => true, 'is_active' => true, 'password_changed' => true]);
        $employee = Employee::create([
            'user_id' => $empUser->id,
            'staff_no' => 'EMP-1002',
            'full_name_en' => 'Teshome Lemma',
            'base_salary' => 10000,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'February 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'status' => 'processing',
        ]);

        Payslip::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'working_days' => 26,
            'gross' => 10000,
            'taxable_income' => 9300,
            'income_tax' => 1835,
            'employee_pension' => 700,
            'employer_pension' => 1100,
            'net_pay' => 7465,
            'status' => 'draft',
        ]);

        // Tax Schedule
        $taxResponse = $this->actingAs($user)->get(route('finance.payroll.export.tax-schedule', $period));
        $taxResponse->assertStatus(200);
        $this->assertStringContainsString('STAFF_NO,EMPLOYEE_NAME,BASIC_SALARY,TAXABLE_ALLOWANCES,OVERTIME,TAXABLE_INCOME,PIT_WITHHELD', $taxResponse->streamedContent());

        // Pension Schedule
        $pensionResponse = $this->actingAs($user)->get(route('finance.payroll.export.pension-schedule', $period));
        $pensionResponse->assertStatus(200);
        $this->assertStringContainsString('STAFF_NO,EMPLOYEE_NAME,BASIC_SALARY,EMPLOYEE_PENSION_7PCT,EMPLOYER_PENSION_11PCT,TOTAL_PENSION_18PCT', $pensionResponse->streamedContent());
        $this->assertStringContainsString('700.00,1100.00,1800.00', $pensionResponse->streamedContent());
    }

    public function test_variance_presenter_compares_periods_correctly(): void
    {
        $empUser = User::factory()->create(['name' => 'Almaz Ayana', 'is_approved' => true, 'is_active' => true, 'password_changed' => true]);
        $employee = Employee::create([
            'user_id' => $empUser->id,
            'staff_no' => 'EMP-1003',
            'full_name_en' => 'Almaz Ayana',
            'base_salary' => 10000,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        $periodJan = PayrollPeriod::create([
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'locked',
        ]);

        $periodFeb = PayrollPeriod::create([
            'name' => 'February 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'status' => 'processing',
        ]);

        Payslip::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $periodJan->id,
            'working_days' => 26,
            'gross' => 10000,
            'income_tax' => 1835,
            'employee_pension' => 700,
            'employer_pension' => 1100,
            'net_pay' => 7465,
            'status' => 'locked',
        ]);

        Payslip::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $periodFeb->id,
            'working_days' => 26,
            'gross' => 12000,
            'income_tax' => 2400,
            'employee_pension' => 840,
            'employer_pension' => 1320,
            'net_pay' => 8760,
            'status' => 'draft',
        ]);

        $variance = PayrollVariancePresenter::analyze($periodFeb);

        $this->assertTrue($variance['has_previous']);
        $this->assertEquals('January 2026', $variance['previous_period_name']);
        $this->assertEquals(8760.0, $variance['summary']['net_pay']['current']);
        $this->assertEquals(7465.0, $variance['summary']['net_pay']['previous']);
        $this->assertCount(1, $variance['variations']);
        $this->assertEquals('increase', $variance['variations'][0]['type']);
    }
}
