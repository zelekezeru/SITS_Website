<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate(['name' => 'SUPERADMIN']);
        Role::firstOrCreate(['name' => 'ADMIN']);
        Role::firstOrCreate(['name' => 'EDITOR']);
        Role::firstOrCreate(['name' => 'TRAINER']);
        Role::firstOrCreate(['name' => 'STUDENT']);
        Role::firstOrCreate(['name' => 'STAFF']);
        Role::firstOrCreate(['name' => 'LIBRARIAN']);
        Role::firstOrCreate(['name' => 'USER']);
    }
}
