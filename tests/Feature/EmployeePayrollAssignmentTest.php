<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Covers the shared employee payroll-config modal's assignment endpoint — the
 * one reached from both the employee record and the payroll period page.
 */
class EmployeePayrollAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Employee $employee;

    private PayrollComponent $allowance;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('manage payroll', 'web');

        $this->user = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $this->user->givePermissionTo('manage payroll');

        $empUser = User::factory()->create([
            'name' => 'Kebede Alemu', 'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $empUser->id,
            'staff_no' => 'EMP-2001',
            'full_name_en' => 'Kebede Alemu',
            'base_salary' => 12000,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        $this->allowance = PayrollComponent::create([
            'name' => 'Housing Allowance',
            'kind' => 'allowance',
            'calc_type' => 'fixed',
            'applies_to' => 'all',
            'sheet_column' => 'housing_allowance',
            'is_active' => true,
        ]);
    }

    /** A recurring allowance submits with no period bounds at all. */
    public function test_monthly_allowance_can_be_assigned(): void
    {
        $this->actingAs($this->user)
            ->postJson("/finance/employees/{$this->employee->id}/assignments", [
                'payroll_component_id' => $this->allowance->id,
                'amount' => 1500,
                'schedule_type' => 'monthly',
                'start_period_id' => null,
                'end_period_id' => null,
                'note' => '',
            ])
            ->assertOk();

        $assignment = PayrollComponentAssignment::first();
        $this->assertNotNull($assignment);
        $this->assertEquals(1500, (float) $assignment->amount);
        $this->assertNull($assignment->start_period_id);
    }

    /**
     * Regression: the payroll period page sends scheduleTypes as {value,label}
     * pairs. When the select bound the whole option the request carried an object
     * and every save came back 422 — the value alone must be what reaches here.
     */
    public function test_schedule_type_must_be_a_plain_enum_value(): void
    {
        $this->actingAs($this->user)
            ->postJson("/finance/employees/{$this->employee->id}/assignments", [
                'payroll_component_id' => $this->allowance->id,
                'amount' => 1500,
                'schedule_type' => ['value' => 'monthly', 'label' => 'Monthly (recurring)'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedule_type');
    }

    /** The period selects send '' for "none"; that must not fail the exists rule. */
    public function test_blank_period_ids_are_treated_as_none(): void
    {
        $this->actingAs($this->user)
            ->postJson("/finance/employees/{$this->employee->id}/assignments", [
                'payroll_component_id' => $this->allowance->id,
                'amount' => 800,
                'schedule_type' => 'monthly',
                'start_period_id' => '',
                'end_period_id' => '',
            ])
            ->assertOk();

        $this->assertNull(PayrollComponentAssignment::first()->start_period_id);
    }

    /** A one-time entry without its period reports a field error, not a bare 422. */
    public function test_one_time_assignment_requires_a_start_period(): void
    {
        $this->actingAs($this->user)
            ->postJson("/finance/employees/{$this->employee->id}/assignments", [
                'payroll_component_id' => $this->allowance->id,
                'amount' => 500,
                'schedule_type' => 'one_time',
                'start_period_id' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_period_id');
    }

    /** A one-time entry is pinned to a single period on both ends. */
    public function test_one_time_assignment_pins_both_bounds(): void
    {
        $period = PayrollPeriod::create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
        ]);

        $this->actingAs($this->user)
            ->postJson("/finance/employees/{$this->employee->id}/assignments", [
                'payroll_component_id' => $this->allowance->id,
                'amount' => 500,
                'schedule_type' => 'one_time',
                'start_period_id' => $period->id,
            ])
            ->assertOk();

        $assignment = PayrollComponentAssignment::first();
        $this->assertEquals($period->id, $assignment->start_period_id);
        $this->assertEquals($period->id, $assignment->end_period_id);
    }

    /** Statutory components apply by scheme and are rejected with a field error. */
    public function test_statutory_components_cannot_be_assigned(): void
    {
        $pension = PayrollComponent::create([
            'name' => 'Pension (Employee 7%)',
            'kind' => 'statutory',
            'calc_type' => 'percent',
            'rate' => 7,
            'side' => 'employee',
            'applies_to' => 'pension_members',
            'sheet_column' => 'employee_pension',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->postJson("/finance/employees/{$this->employee->id}/assignments", [
                'payroll_component_id' => $pension->id,
                'amount' => 0,
                'schedule_type' => 'monthly',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payroll_component_id');
    }

    /** The config endpoint reads component `kind` — it used to crash on `type`. */
    public function test_get_config_returns_assignments(): void
    {
        PayrollComponentAssignment::create([
            'employee_id' => $this->employee->id,
            'payroll_component_id' => $this->allowance->id,
            'amount' => 1500,
            'schedule_type' => 'monthly',
        ]);

        $this->actingAs($this->user)
            ->getJson("/finance/employees/{$this->employee->id}/payroll-config")
            ->assertOk()
            ->assertJsonPath('assignments.0.component_type', 'allowance')
            ->assertJsonPath('assignments.0.component_name', 'Housing Allowance');
    }
}
