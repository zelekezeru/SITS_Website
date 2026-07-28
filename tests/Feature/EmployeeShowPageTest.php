<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The admin employee record page must render for a President / Super Admin. */
class EmployeeShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_show_page_renders(): void
    {
        $role = Role::findOrCreate('President / Super Admin', 'web');
        foreach (['manage employees', 'view employees', 'configure payroll'] as $p) {
            $role->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }

        $admin = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $admin->assignRole($role);

        $empUser = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $empUser->id,
            'staff_no' => 'EMP-9001',
            'full_name_en' => 'Render Test',
            'base_salary' => 10000,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        $this->actingAs($admin)
            ->get("/admin/employees/{$employee->id}")
            ->assertOk();
    }
}
