<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\PayrollController;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The President-side "Run Payroll" action on the period page: offered only when
 * Finance has not prepared the period and it is not yet submitted or approved.
 */
class PayrollPeriodRunCapabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $president;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['manage payroll', 'prepare payroll', 'approve payroll', 'submit payroll'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->president = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $this->president->givePermissionTo(['manage payroll', 'prepare payroll', 'approve payroll']);
    }

    private function period(string $status): PayrollPeriod
    {
        return PayrollPeriod::create([
            'name' => 'March 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => $status,
        ]);
    }

    private function givePeriodAPayslip(PayrollPeriod $period): void
    {
        $empUser = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $empUser->id,
            'staff_no' => 'EMP-3001',
            'full_name_en' => 'Selam Tadesse',
            'base_salary' => 9000,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        Payslip::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'working_days' => 26,
            'gross' => 9000,
            'net_pay' => 7000,
            'status' => 'draft',
        ]);
    }

    private function canRun(PayrollPeriod $period, bool $isAdmin = true): bool
    {
        return PayrollController::periodProps($period, $this->president, $isAdmin)['can']['run'];
    }

    public function test_offered_when_finance_has_not_prepared_an_open_period(): void
    {
        $this->assertTrue($this->canRun($this->period('open')));
    }

    /** A returned period is back with Finance but still unprepared — still offered. */
    public function test_offered_on_a_rejected_period_with_no_payslips(): void
    {
        $this->assertTrue($this->canRun($this->period('rejected')));
    }

    public function test_hidden_once_finance_has_prepared_the_period(): void
    {
        $period = $this->period('processing');
        $this->givePeriodAPayslip($period);

        $this->assertFalse($this->canRun($period));
    }

    public function test_hidden_once_submitted_for_approval(): void
    {
        $this->assertFalse($this->canRun($this->period('pending_approval')));
    }

    public function test_hidden_once_approved(): void
    {
        $this->assertFalse($this->canRun($this->period('approved')));
    }

    public function test_hidden_on_locked_and_paid_periods(): void
    {
        $this->assertFalse($this->canRun($this->period('locked')));
        $this->assertFalse($this->canRun($this->period('paid')));
    }

    /** Finance sees their own Recompute action, never the President fallback. */
    public function test_hidden_from_the_finance_view(): void
    {
        $this->assertFalse($this->canRun($this->period('open'), isAdmin: false));
    }

    public function test_hidden_without_the_prepare_payroll_permission(): void
    {
        $viewer = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $viewer->givePermissionTo(['manage payroll', 'approve payroll']);

        $props = PayrollController::periodProps($this->period('open'), $viewer, isAdmin: true);

        $this->assertFalse($props['can']['run']);
    }
}
