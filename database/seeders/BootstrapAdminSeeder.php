<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BootstrapAdminSeeder extends Seeder
{
    /**
     * Creates a fallback admin account in case UserRoleSeeder has not run yet.
     * The real President (Endale Sebsebe Mekonnen) is seeded via UserRoleSeeder.
     *
     * CHANGE THE PASSWORD IMMEDIATELY after first login.
     */
    public function run(): void
    {
        $email    = 'admin@sits.edu';
        $password = 'ChangeMe@SITS2026';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'             => 'SITS Administrator',
                'password'         => Hash::make($password),
                'default_password' => $password,
                'is_approved'      => true,
                'is_active'        => true,
                'password_changed' => false,
                'email_verified_at' => now(),
            ]
        );

        $role = Role::firstOrCreate(['name' => 'President / Super Admin']);
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->command?->warn("Bootstrap admin: {$email} / {$password}  — change this password immediately.");
    }
}
