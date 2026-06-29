<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeds real SITS staff from the production database.
 *
 * Each user is created with a uniform default password for initial login;
 * the original hashed passwords are not carried over. Users map to the new
 * PMS schema roles: President / Super Admin, Vice President, Dean of the
 * Seminary, Department Head, Employee.
 *
 * Run after: RolesAndPermissionsSeeder, OrganizationSeeder.
 */
class UserRoleSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Each entry: [name, phone, email, role, department_name_en, is_approved, is_active]
     *
     * Roles use the new PMS role names.
     * department_name_en must match OrganizationSeeder exactly.
     */
    public function run(): void
    {
        $staff = [
            [
                'name'       => 'Endale Sebsebe Mekonnen',
                'email'      => 'esebsebe@yahoo.com',
                'phone'      => '0911914027',
                'role'       => 'President / Super Admin',
                'department' => 'Office of the President',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Zerubbabel Zeleke',
                'email'      => 'zelekezeru@gmail.com',
                'phone'      => '0975210097',
                'role'       => 'Employee',
                'department' => 'Operations & Administration',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Abate Dejene Lemma',
                'email'      => 'abeyeenatu1980@gmail.com',
                'phone'      => '0912243902',
                'role'       => 'Department Head',
                'department' => 'Academic Affairs',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Abenezer Ayalew Mekonnen',
                'email'      => 'abensew13@gmail.com',
                'phone'      => '0926493321',
                'role'       => 'Employee',
                'department' => 'Academic Affairs',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Alte Agegnew Tadese',
                'email'      => 'agegnehualte@gmail.com',
                'phone'      => '0995520631',
                'role'       => 'Department Head',
                'department' => 'Registrar & Alumni',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Amarech Abrham',
                'email'      => 'amarech.abrham@sits.edu.et',
                'phone'      => '0932617906',
                'role'       => 'Finance Officer',
                'department' => 'Finance',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Amarech Otoro',
                'email'      => 'amarech.otoro@sits.edu.et',
                'phone'      => '0920971053',
                'role'       => 'Employee',
                'department' => 'Operations & Administration',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Azeb Buche',
                'email'      => 'azeb.buche@sits.edu.et',
                'phone'      => '0938980434',
                'role'       => 'Employee',
                'department' => 'Satellite & Learning Sites',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Birhanu Gelaye',
                'email'      => 'birhanu.gelaye@sits.edu.et',
                'phone'      => '0996757418',
                'role'       => 'Employee',
                'department' => 'Satellite & Learning Sites',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Elfinesh Yadesa',
                'email'      => 'elfinesh.yadesa@sits.edu.et',
                'phone'      => '0911166059',
                'role'       => 'Employee',
                'department' => 'Operations & Administration',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Geda Tufule',
                'email'      => 'gedatufule9@gmail.com',
                'phone'      => '0911166059',
                'role'       => 'Department Head',
                'department' => 'Operations & Administration',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Kalkidan Eshetu',
                'email'      => 'eshetukalkidan704@gmail.com',
                'phone'      => '0916029589',
                'role'       => 'Employee',
                'department' => 'Finance',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Mesele Dawit',
                'email'      => 'mesele.dawit@sits.edu.et',
                'phone'      => '0964174359',
                'role'       => 'Employee',
                'department' => 'Operations & Administration',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Meskerem Aseffa',
                'email'      => 'lebamesew@gmail.com',
                'phone'      => '098918928',
                'role'       => 'Department Head',
                'department' => 'Operations & Administration',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Mesganu Petros',
                'email'      => 'pmesge@gmail.com',
                'phone'      => '0937216471',
                'role'       => 'Vice President',
                'department' => 'Academic Affairs',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Misale Getu Ayalew',
                'email'      => 'mesalegetu@yahoo.com',
                'phone'      => '0916823018',
                'role'       => 'Department Head',
                'department' => 'Student Affairs',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Pastor Zekariyas Chinasho',
                'email'      => 'zekariyas.chinasho@sits.edu.et',
                'phone'      => '0910713798',
                'role'       => 'Employee',
                'department' => 'Satellite & Learning Sites',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Selamawit Yared',
                'email'      => 'yaredselamawit@yahoo.com',
                'phone'      => '0916406343',
                'role'       => 'Department Head',
                'department' => 'Library',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Tamiru Lijalem',
                'email'      => 'lijalem.tamiru@gmail.com',
                'phone'      => '+251910921472',
                'role'       => 'Department Head',
                'department' => 'Open Distance & eLearning (ODeL)',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Tesfaye Gebre',
                'email'      => 'tesfaye.gebre@sits.edu.et',
                'phone'      => '0916831834',
                'role'       => 'Department Head',
                'department' => 'Satellite & Learning Sites',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Tesfaye Gebre Oke',
                'email'      => 'tesfayegebre18@gmail.com',
                'phone'      => '0916831834',
                'role'       => 'Employee',
                'department' => 'Satellite & Learning Sites',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Yetnayet Nigatu Entele',
                'email'      => 'yetnayet.nigatu@sits.edu.et',
                'phone'      => '0913424039',
                'role'       => 'Employee',
                'department' => 'Registrar & Alumni',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Yilma Gezmu Mengesha',
                'email'      => 'yilmagezmu@yahoo.com',
                'phone'      => '0911733240',
                'role'       => 'Department Head',
                'department' => 'Academic Affairs',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Zeleke Abisso',
                'email'      => 'abissozeleke@gmail.com',
                'phone'      => '09168606',
                'role'       => 'Employee',
                'department' => 'Open Distance & eLearning (ODeL)',
                'approved'   => true,
                'active'     => true,
            ],
            [
                'name'       => 'Zewude Zeleke',
                'email'      => 'zewude.zeleke@sits.edu.et',
                'phone'      => '0911747750',
                'role'       => 'Employee',
                'department' => 'Satellite & Learning Sites',
                'approved'   => true,
                'active'     => true,
            ],
        ];

        foreach ($staff as $person) {
            $department = Department::where('name_en', $person['department'])->first();

            $user = User::firstOrCreate(
                ['email' => $person['email']],
                [
                    'name'             => $person['name'],
                    'password'         => Hash::make(self::DEFAULT_PASSWORD),
                    'default_password' => self::DEFAULT_PASSWORD,
                    'is_approved'      => $person['approved'],
                    'is_active'        => $person['active'],
                    'password_changed' => false,
                    'email_verified_at' => now(),
                ]
            );

            // Assign role
            $role = Role::firstOrCreate(['name' => $person['role']]);
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            // Create corresponding employee record. The staff number is derived
            // from the user id, but guard against a row already claiming it (e.g.
            // an imported dump or a prior partial seed) so re-seeding never trips
            // the staff_no unique index. Include soft-deleted rows in checking.
            $base = 'SITS-' . date('Y') . '-' . sprintf('%04d', $user->id);
            $staffNo = $base;
            $suffix = 1;
            while (Employee::withTrashed()->where('staff_no', $staffNo)->where('user_id', '!=', $user->id)->exists()) {
                $staffNo = $base . '-' . $suffix++;
            }

            $employee = Employee::withTrashed()->where('user_id', $user->id)->first();
            if ($employee) {
                if ($employee->trashed()) {
                    $employee->restore();
                }
                $employee->update([
                    'staff_no'        => $staffNo,
                    'full_name_en'    => $person['name'],
                    'department_id'   => $department?->id,
                    'employment_type' => EmploymentType::FullTime,
                    'is_active'       => $person['active'],
                ]);
            } else {
                Employee::create([
                    'user_id'         => $user->id,
                    'staff_no'        => $staffNo,
                    'full_name_en'    => $person['name'],
                    'department_id'   => $department?->id,
                    'employment_type' => EmploymentType::FullTime,
                    'is_active'       => $person['active'],
                ]);
            }
        }

        $this->command?->info('Seeded ' . count($staff) . ' real SITS staff members.');
    }
}
