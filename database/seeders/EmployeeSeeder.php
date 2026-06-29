<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed the users
        $users = [
            ['id' => 1, 'name' => 'SITS Administrator', 'email' => 'admin@sits.edu', 'role' => 'SUPERADMIN'],
            ['id' => 2, 'name' => 'Endale Sebsebe Mekonnen', 'email' => 'esebsebe@yahoo.com', 'role' => 'STAFF'],
            ['id' => 3, 'name' => 'Zerubbabel Zeleke', 'email' => 'zelekezeru@gmail.com', 'role' => 'ADMIN'],
            ['id' => 4, 'name' => 'Abate Dejene Lemma', 'email' => 'abeyeenatu1980@gmail.com', 'role' => 'TRAINER'],
            ['id' => 5, 'name' => 'Abenezer Ayalew Mekonnen', 'email' => 'abensew13@gmail.com', 'role' => 'TRAINER'],
            ['id' => 6, 'name' => 'Alte Agegnew Tadese', 'email' => 'agegnehualte@gmail.com', 'role' => 'STAFF'],
            ['id' => 7, 'name' => 'Amarech Abrham', 'email' => 'amarech.abrham@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 8, 'name' => 'Amarech Otoro', 'email' => 'amarech.otoro@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 9, 'name' => 'Azeb Buche', 'email' => 'azeb.buche@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 10, 'name' => 'Birhanu Gelaye', 'email' => 'birhanu.gelaye@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 11, 'name' => 'Elfinesh Yadesa', 'email' => 'elfinesh.yadesa@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 12, 'name' => 'Geda Tufule', 'email' => 'gedatufule9@gmail.com', 'role' => 'STAFF'],
            ['id' => 13, 'name' => 'Kalkidan Eshetu', 'email' => 'eshetukalkidan704@gmail.com', 'role' => 'STAFF'],
            ['id' => 14, 'name' => 'Mesele Dawit', 'email' => 'mesele.dawit@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 15, 'name' => 'Meskerem Aseffa', 'email' => 'lebamesew@gmail.com', 'role' => 'STAFF'],
            ['id' => 16, 'name' => 'Mesganu Petros', 'email' => 'pmesge@gmail.com', 'role' => 'TRAINER'],
            ['id' => 17, 'name' => 'Misale Getu Ayalew', 'email' => 'mesalegetu@yahoo.com', 'role' => 'STAFF'],
            ['id' => 18, 'name' => 'Pastor Zekariyas Chinasho', 'email' => 'zekariyas.chinasho@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 19, 'name' => 'Selamawit Yared', 'email' => 'yaredselamawit@yahoo.com', 'role' => 'LIBRARIAN'],
            ['id' => 20, 'name' => 'Tamiru Lijalem', 'email' => 'lijalem.tamiru@gmail.com', 'role' => 'STAFF'],
            ['id' => 21, 'name' => 'Tesfaye Gebre', 'email' => 'tesfaye.gebre@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 22, 'name' => 'Tesfaye Gebre Oke', 'email' => 'tesfayegebre18@gmail.com', 'role' => 'STAFF'],
            ['id' => 23, 'name' => 'Yetnayet Nigatu Entele', 'email' => 'yetnayet.nigatu@sits.edu.et', 'role' => 'STAFF'],
            ['id' => 24, 'name' => 'Yilma Gezmu Mengesha', 'email' => 'yilmagezmu@yahoo.com', 'role' => 'TRAINER'],
            ['id' => 25, 'name' => 'Zeleke Abisso', 'email' => 'abissozeleke@gmail.com', 'role' => 'STAFF'],
            ['id' => 26, 'name' => 'Zewude Zeleke', 'email' => 'zewude.zeleke@sits.edu.et', 'role' => 'STAFF'],
        ];

        $defaultPassword = Hash::make('password');

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $defaultPassword,
                    'role' => $userData['role'],
                ]
            );

            // Assign Spatie Role
            $role = Role::where('name', $userData['role'])->first();
            if ($role && !$user->hasRole($role->name)) {
                $user->assignRole($role);
            }
        }

        // 2. Seed the employees (with salaries)
        $employees = [
            ['id' => 1, 'user_id' => 2, 'staff_no' => 'SITS-2026-0002', 'full_name_en' => 'Endale Sebsebe Mekonnen', 'department_id' => 1, 'base_salary' => 0.00],
            ['id' => 2, 'user_id' => 3, 'staff_no' => 'SITS-2026-0003', 'full_name_en' => 'Zerubbabel Zeleke', 'department_id' => 7, 'base_salary' => 15000.00],
            ['id' => 3, 'user_id' => 4, 'staff_no' => 'SITS-2026-0004', 'full_name_en' => 'Abate Dejene Lemma', 'department_id' => 2, 'base_salary' => 28461.54],
            ['id' => 4, 'user_id' => 5, 'staff_no' => 'SITS-2026-0005', 'full_name_en' => 'Abenezer Ayalew Mekonnen', 'department_id' => 2, 'base_salary' => 17000.00],
            ['id' => 5, 'user_id' => 6, 'staff_no' => 'SITS-2026-0006', 'full_name_en' => 'Alte Agegnew Tadese', 'department_id' => 5, 'base_salary' => 11700.00],
            ['id' => 6, 'user_id' => 7, 'staff_no' => 'SITS-2026-0007', 'full_name_en' => 'Amarech Abrham', 'department_id' => 8, 'base_salary' => 8095.97],
            ['id' => 7, 'user_id' => 8, 'staff_no' => 'SITS-2026-0008', 'full_name_en' => 'Amarech Otoro', 'department_id' => 7, 'base_salary' => 5972.71],
            ['id' => 8, 'user_id' => 9, 'staff_no' => 'SITS-2026-0009', 'full_name_en' => 'Azeb Buche', 'department_id' => 4, 'base_salary' => 5972.71],
            ['id' => 9, 'user_id' => 10, 'staff_no' => 'SITS-2026-0010', 'full_name_en' => 'Birhanu Gelaye', 'department_id' => 4, 'base_salary' => 29671.51],
            ['id' => 10, 'user_id' => 11, 'staff_no' => 'SITS-2026-0011', 'full_name_en' => 'Elfinesh Yadesa', 'department_id' => 7, 'base_salary' => 10407.97],
            ['id' => 11, 'user_id' => 12, 'staff_no' => 'SITS-2026-0012', 'full_name_en' => 'Geda Tufule', 'department_id' => 7, 'base_salary' => 27871.51],
            ['id' => 12, 'user_id' => 13, 'staff_no' => 'SITS-2026-0013', 'full_name_en' => 'Kalkidan Eshetu', 'full_name_am' => 'ቃልኪዳን እሸቱ', 'position_id' => 11, 'department_id' => 8, 'base_salary' => 16257.55],
            ['id' => 13, 'user_id' => 14, 'staff_no' => 'SITS-2026-0014', 'full_name_en' => 'Mesele Dawit', 'department_id' => 7, 'base_salary' => 7835.92],
            ['id' => 14, 'user_id' => 15, 'staff_no' => 'SITS-2026-0015', 'full_name_en' => 'Meskerem Aseffa', 'department_id' => 7, 'base_salary' => 22838.10],
            ['id' => 15, 'user_id' => 16, 'staff_no' => 'SITS-2026-0016', 'full_name_en' => 'Mesganu Petros', 'department_id' => 2, 'base_salary' => 28012.70],
            ['id' => 16, 'user_id' => 17, 'staff_no' => 'SITS-2026-0017', 'full_name_en' => 'Misale Getu Ayalew', 'department_id' => 6, 'base_salary' => 20000.00],
            ['id' => 17, 'user_id' => 18, 'staff_no' => 'SITS-2026-0018', 'full_name_en' => 'Zekariyas Chinasho', 'department_id' => 4, 'base_salary' => 22200.00],
            ['id' => 18, 'user_id' => 19, 'staff_no' => 'SITS-2026-0019', 'full_name_en' => 'Selamawit Yared', 'department_id' => 9, 'base_salary' => 16100.61],
            ['id' => 19, 'user_id' => 20, 'staff_no' => 'SITS-2026-0020', 'full_name_en' => 'Tamiru Lijalem', 'department_id' => 3, 'base_salary' => 29485.79],
            ['id' => 20, 'user_id' => 21, 'staff_no' => 'SITS-2026-0021', 'full_name_en' => 'Tesfaye Gebre', 'department_id' => 4, 'base_salary' => 0.00],
            ['id' => 21, 'user_id' => 22, 'staff_no' => 'SITS-2026-0022', 'full_name_en' => 'Tesfaye Gebre Oke', 'department_id' => 4, 'base_salary' => 21593.34],
            ['id' => 22, 'user_id' => 23, 'staff_no' => 'SITS-2026-0023', 'full_name_en' => 'Yetnayet Nigatu Entele', 'department_id' => 5, 'base_salary' => 14008.45],
            ['id' => 23, 'user_id' => 24, 'staff_no' => 'SITS-2026-0024', 'full_name_en' => 'Yilma Gezmu Mengesha', 'department_id' => 2, 'base_salary' => 25103.47],
            ['id' => 24, 'user_id' => 25, 'staff_no' => 'SITS-2026-0025', 'full_name_en' => 'Zeleke Abisso', 'department_id' => 3, 'base_salary' => 15221.97],
            ['id' => 25, 'user_id' => 26, 'staff_no' => 'SITS-2026-0026', 'full_name_en' => 'Zewude Zeleke', 'department_id' => 4, 'base_salary' => 40011.12],
        ];

        foreach ($employees as $empData) {
            // Find the user to get the correct user_id (since we created them above)
            $email = $users[$empData['user_id'] - 1]['email'];
            $user = User::where('email', $email)->first();

            if ($user) {
                Employee::updateOrCreate(
                    ['staff_no' => $empData['staff_no']],
                    [
                        'user_id' => $user->id,
                        'full_name_en' => $empData['full_name_en'],
                        'full_name_am' => $empData['full_name_am'] ?? null,
                        'position_id' => $empData['position_id'] ?? null,
                        'department_id' => $empData['department_id'],
                        'base_salary' => $empData['base_salary'],
                    ]
                );
            }
        }
    }
}
