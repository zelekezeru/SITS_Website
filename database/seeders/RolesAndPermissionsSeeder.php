<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Roles mirror the SITS hierarchy. Super Admin gets everything; lower roles get an
     * explicit subset for clarity and auditability.
     */
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        $permissions = [
            'manage strategic plan', 'view strategic plan',
            'manage employees', 'view employees', 'view department employees',
            'manage job descriptions', 'crud kpis', 'approve kpis', 'confirm kpis', 'view kpis',
            'create tasks', 'manage all tasks', 'manage department tasks', 'manage own tasks',
            'manage deliverables', 'comment tasks',
            'run evaluations', 'score evaluations', 'finalize evaluations', 'view own evaluations',
            'manage payroll', 'validate attendance', 'view payslips', 'edit tax config',
            'prepare payroll', 'manage deductions', 'submit payroll', 'approve payroll', 'export payroll',
            'configure payroll', 'upload attendance', 'approve attendance',
            'create attendance permission', 'approve attendance permission',
            'recommend increments', 'approve increments',
            'manage users', 'approve users', 'reset passwords',
            'view executive reports', 'view department reports', 'export reports',
            'manage conduct issues', 'create conduct issues', 'manage department conduct', 'manage conduct decisions',
            'manage closed days', 'create mass permission', 'approve mass permission',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $all = Permission::pluck('name')->toArray();

        $president = Role::firstOrCreate(['name' => 'President / Super Admin']);
        $president->syncPermissions($all);

        $vp = Role::firstOrCreate(['name' => 'Vice President']);
        $vp->syncPermissions([
            'view strategic plan', 'view employees', 'view kpis',
            'manage all tasks', 'run evaluations', 'finalize evaluations',
            'manage payroll', 'approve increments', 'view payslips',
            'view executive reports', 'export reports', 'approve users',
            'manage conduct issues', 'manage conduct decisions',
            // A second authoriser so the two-person mass-permission rule is satisfiable.
            'approve attendance permission', 'approve mass permission',
        ]);

        $dean = Role::firstOrCreate(['name' => 'Dean of the Seminary']);
        $dean->syncPermissions([
            'view strategic plan', 'manage job descriptions', 'crud kpis', 'approve kpis',
            'view department employees', 'create tasks', 'manage department tasks',
            'score evaluations', 'manage deliverables', 'comment tasks',
            'view department reports', 'export reports',
            'create conduct issues', 'manage department conduct',
        ]);

        $ops = Role::firstOrCreate(['name' => 'Operational Manager']);
        $ops->syncPermissions([
            'view department employees', 'view employees', 'approve kpis',
            'create tasks', 'manage department tasks', 'manage deliverables', 'comment tasks',
            'view department reports', 'export reports',
            'create conduct issues', 'manage department conduct',
            // Payroll & attendance — same operational reach as Finance (no self-approval).
            'validate attendance', 'upload attendance', 'create attendance permission',
            'manage payroll', 'prepare payroll', 'submit payroll', 'export payroll',
            'manage deductions', 'view payslips',
            'create mass permission',
        ]);

        // Finance Officer: a regular employee who additionally prepares payroll,
        // manages per-period deductions and exports — but cannot self-approve.
        $finance = Role::firstOrCreate(['name' => 'Finance Officer']);
        $finance->syncPermissions([
            'view employees', 'view payslips',
            'manage payroll', 'prepare payroll', 'manage deductions', 'submit payroll', 'export payroll',
            'upload attendance',
            'view strategic plan', 'create tasks', 'manage own tasks', 'comment tasks', 'view own evaluations',
        ]);

        $head = Role::firstOrCreate(['name' => 'Department Head']);
        $head->syncPermissions([
            'view department employees', 'create tasks', 'manage department tasks',
            'approve kpis', 'score evaluations', 'manage deliverables', 'comment tasks',
            'recommend increments', 'view department reports', 'export reports',
            'create conduct issues', 'manage department conduct',
        ]);

        $registrar = Role::firstOrCreate(['name' => 'Registrar']);
        $registrar->syncPermissions([
            'view employees', 'view department reports', 'comment tasks',
        ]);

        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $employee->syncPermissions([
            'view strategic plan', 'create tasks', 'manage own tasks',
            'comment tasks', 'view own evaluations', 'view payslips',
        ]);
    }
}
