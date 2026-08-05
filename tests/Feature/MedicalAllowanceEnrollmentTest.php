<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Medical allowance enrollment is a request → admin-approval flow, same
 * maker/checker split as medical allowance claims — checking "Apply Medical
 * Allowance" on the Payroll Config → Per Employee panel never flips
 * eligibility on by itself; only an explicit admin approval does.
 */
class MedicalAllowanceEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('request medical allowance', 'web');
        Permission::findOrCreate('approve medical allowance', 'web');
    }

    // ---- Model-level state machine ------------------------------------------

    public function test_requesting_does_not_enable_eligibility_yet(): void
    {
        $employee = $this->employee();
        $requester = User::factory()->create();

        $employee->requestMedicalAllowance($requester);
        $employee->refresh();

        $this->assertTrue($employee->medical_allowance_requested);
        $this->assertFalse($employee->medical_allowance_enabled);
        $this->assertEquals('pending', $employee->medicalAllowanceStatus());
        $this->assertFalse($employee->isMedicalAllowanceEligible());
    }

    public function test_approving_flips_eligibility_on(): void
    {
        $employee = $this->employee();
        $requester = User::factory()->create();
        $admin = User::factory()->create();

        $employee->requestMedicalAllowance($requester);
        $employee->approveMedicalAllowance($admin);
        $employee->refresh();

        $this->assertTrue($employee->medical_allowance_enabled);
        $this->assertFalse($employee->medical_allowance_requested);
        $this->assertEquals('enrolled', $employee->medicalAllowanceStatus());
        $this->assertTrue($employee->isMedicalAllowanceEligible());
        $this->assertEquals($admin->id, $employee->medical_allowance_reviewed_by);
    }

    public function test_rejecting_keeps_eligibility_off_and_records_the_reason(): void
    {
        $employee = $this->employee();
        $requester = User::factory()->create();
        $admin = User::factory()->create();

        $employee->requestMedicalAllowance($requester);
        $employee->rejectMedicalAllowance($admin, 'Not eligible this cycle');
        $employee->refresh();

        $this->assertFalse($employee->medical_allowance_enabled);
        $this->assertFalse($employee->medical_allowance_requested);
        $this->assertEquals('rejected', $employee->medicalAllowanceStatus());
        $this->assertEquals('Not eligible this cycle', $employee->medical_allowance_rejection_reason);
    }

    /** A rejected employee can be requested again — the reason clears once a new request is filed. */
    public function test_a_rejected_employee_can_be_requested_again(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();

        $employee->requestMedicalAllowance($admin);
        $employee->rejectMedicalAllowance($admin, 'Try later');
        $employee->requestMedicalAllowance($admin);
        $employee->refresh();

        $this->assertEquals('pending', $employee->medicalAllowanceStatus());
        $this->assertNull($employee->medical_allowance_rejection_reason);
    }

    public function test_removing_an_enrolled_employee_disables_eligibility_immediately(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();

        $employee->requestMedicalAllowance($admin);
        $employee->approveMedicalAllowance($admin);
        $employee->removeMedicalAllowance();
        $employee->refresh();

        $this->assertFalse($employee->medical_allowance_enabled);
        $this->assertFalse($employee->isMedicalAllowanceEligible());
        $this->assertEquals('none', $employee->medicalAllowanceStatus());
    }

    // ---- HTTP / permission gating -------------------------------------------

    public function test_requesting_requires_the_request_permission(): void
    {
        $employee = $this->employee();
        $unauthorized = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        $this->actingAs($unauthorized)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/request")
            ->assertForbidden();

        $this->assertFalse($employee->fresh()->medical_allowance_requested);
    }

    public function test_approving_requires_the_approve_permission_not_just_request(): void
    {
        $employee = $this->employee();
        $requester = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $requester->givePermissionTo('request medical allowance');

        $this->actingAs($requester)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/request")
            ->assertRedirect();

        $employee->refresh();
        $this->assertTrue($employee->medical_allowance_requested);

        // The same requester cannot also approve — holding only "request" isn't enough.
        $this->actingAs($requester)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/approve")
            ->assertForbidden();

        $this->assertFalse($employee->fresh()->medical_allowance_enabled);
    }

    public function test_full_request_then_approve_cycle_via_http(): void
    {
        $employee = $this->employee();

        $requester = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $requester->givePermissionTo('request medical allowance');

        $admin = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $admin->givePermissionTo('approve medical allowance');

        $this->actingAs($requester)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/request")
            ->assertRedirect();

        $this->actingAs($admin)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/approve")
            ->assertRedirect();

        $employee->refresh();
        $this->assertTrue($employee->medical_allowance_enabled);
        $this->assertFalse($employee->medical_allowance_requested);
        $this->assertEquals($admin->id, $employee->medical_allowance_reviewed_by);
    }

    /** Duplicate requests and approving/rejecting with nothing pending are rejected with a friendly error, not a crash. */
    public function test_duplicate_request_and_approve_without_pending_are_no_ops(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $admin->givePermissionTo(['request medical allowance', 'approve medical allowance']);

        // Approving with nothing pending: no-op, no exception.
        $this->actingAs($admin)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/approve")
            ->assertRedirect();
        $this->assertFalse($employee->fresh()->medical_allowance_enabled);

        // First request succeeds.
        $this->actingAs($admin)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/request")
            ->assertRedirect();

        // A second request while one is already pending doesn't stack/duplicate.
        $this->actingAs($admin)
            ->post("/admin/payroll/config/employees/{$employee->id}/medical-allowance/request")
            ->assertRedirect();
        $this->assertTrue($employee->fresh()->medical_allowance_requested);
    }

    // ---------------------------------------------------------------------

    private function employee(array $attributes = []): Employee
    {
        $user = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        return Employee::create($attributes + [
            'user_id' => $user->id,
            'staff_no' => 'EMP-'.$user->id,
            'full_name_en' => 'Enrollment Test Employee '.$user->id,
            'base_salary' => 15000,
            'legal_daily_hour_limit' => 8,
            'is_active' => true,
            'employment_type' => \App\Enums\EmploymentType::FullTime,
            'hired_at' => '2024-01-01',
        ]);
    }
}
