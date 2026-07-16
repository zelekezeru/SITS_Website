<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the unified SITS platform: ERP (Performance Management) + the public
     * website (marketing site + digital library).
     */
    public function run(): void
    {
        // Singleton row the institution-wide archive hangs documents off.
        Organization::firstOrCreate(['id' => 1], ['name' => 'Shiloh International Theological Seminary']);

        $this->call([
            // ---- RBAC (both schemes) ----
            RolesAndPermissionsSeeder::class,   // ERP roles + permissions
            RoleSeeder::class,                  // Website roles (SUPERADMIN, ADMIN, …)
            LibraryPermissionsSeeder::class,    // Library ILS permissions → unified SITS roles

            // ---- ERP reference data ----
            // OrganizationSeeder::class,          // campuses, departments, positions
            JobDescriptionSeeder::class,        // JDs + versions (needs positions)
            TaxBracketSeeder::class,
            PayrollSettingsSeeder::class,
            PayrollComponentSeeder::class,      // dynamic allowance/deduction/statutory frames
            GradeScaleSeeder::class,
            StrategicPlanSeeder::class,

            // ---- ERP people ----
            BootstrapAdminSeeder::class,        // fallback admin (depends on roles existing)
            UserRoleSeeder::class,              // real SITS staff + ERP roles + employees
            PeriodSeeder::class,                // evaluation & payroll periods (with fortnights)
            PayrollSheetDataSeeder::class,      // real salaries/grades/allowances

            // ---- Website data imports (stubs until Joomla export is pasted) ----
            JoomlaUserImportSeeder::class,
            JstoreSubscriptionSeeder::class,
            JoomlaBookImportSeeder::class,
        ]);

        // Unified super-admin: can use BOTH the website admin panel and the ERP.
        $admin = User::firstOrCreate(
            ['email' => 'admin@sits.edu.et'],
            [
                'name' => 'SITS System Admin',
                'password' => Hash::make('password'),
                'is_approved' => true,
                'is_active' => true,
                'password_changed' => true,
                'email_verified_at' => now(),
            ]
        );

        foreach (['SUPERADMIN', 'President / Super Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $admin->hasRole($role->name)) {
                $admin->assignRole($role);
            }
        }
    }
}
