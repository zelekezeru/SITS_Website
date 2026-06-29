<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * JoomlaUserImportSeeder
 *
 * Imports Joomla user accounts from jos_users into the Laravel users table.
 *
 * HOW TO USE:
 * 1. In Joomla's phpMyAdmin, run:
 *      SELECT id, name, username, email, usertype, block
 *      FROM jos_users
 *      WHERE block = 0
 *      ORDER BY id;
 *
 * 2. Export the result as CSV or JSON.
 *
 * 3. Paste the rows into $joomlaUsers below (or parse the CSV file).
 *
 * 4. Run: php artisan db:seed --class=JoomlaUserImportSeeder
 *
 * Role Mapping (Joomla → Laravel):
 *   Super Administrator → SUPERADMIN
 *   Administrator       → ADMIN
 *   Author / Editor     → EDITOR
 *   Manager             → EDITOR
 *   Registered          → STUDENT (default for all others)
 */
class JoomlaUserImportSeeder extends Seeder
{
    /**
     * Joomla group name → Laravel Spatie role name.
     */
    protected array $roleMap = [
        'Super Administrator' => 'SUPERADMIN',
        'Administrator'       => 'ADMIN',
        'Manager'             => 'EDITOR',
        'Author'              => 'EDITOR',
        'Editor'              => 'EDITOR',
        'Publisher'           => 'EDITOR',
        'Registered'          => 'STUDENT',
        'Guest'               => 'STUDENT',
    ];

    public function run(): void
    {
        // ── STUB DATA ─────────────────────────────────────────────────────────
        // Populate this array from your Joomla database export.
        // The 'group' field should be the Joomla group name from jos_usergroups.
        $joomlaUsers = [
            // Example:
            // [
            //     'joomla_id' => 62,
            //     'name'      => 'Abebe Kebede',
            //     'email'     => 'abebe@example.com',
            //     'group'     => 'Registered',         // Joomla group name
            // ],
        ];

        $defaultPassword = Hash::make('ChangeMe@2026');
        $imported = 0;
        $skipped  = 0;

        foreach ($joomlaUsers as $row) {
            // Skip if email already exists
            if (User::where('email', $row['email'])->exists()) {
                $skipped++;
                continue;
            }

            $roleName = $this->roleMap[$row['group']] ?? 'STUDENT';

            $user = User::create([
                'name'     => $row['name'],
                'email'    => $row['email'],
                'password' => $defaultPassword,
                'role'     => $roleName,
            ]);

            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }

            $imported++;
        }

        $this->command->info("Joomla User Import: {$imported} users imported, {$skipped} skipped (already exist).");

        if (empty($joomlaUsers)) {
            $this->command->warn('⚠  No Joomla users configured yet. Fill in $joomlaUsers in JoomlaUserImportSeeder.php after exporting jos_users.');
        }
    }
}
