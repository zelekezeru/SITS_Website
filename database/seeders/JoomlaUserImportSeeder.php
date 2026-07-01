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
        $joomlaUsers = [];
        try {
            \Illuminate\Support\Facades\DB::connection('joomla')->getPdo();
            
            $prefix = config('database.connections.joomla.prefix', 'josn9_');
            $rows = \Illuminate\Support\Facades\DB::connection('joomla')->select("
                SELECT u.id as joomla_id, u.name, u.email, g.title as group_name
                FROM {$prefix}users u
                LEFT JOIN {$prefix}user_usergroup_map m ON u.id = m.user_id
                LEFT JOIN {$prefix}usergroups g ON m.group_id = g.id
                WHERE u.block = 0
            ");
            
            foreach ($rows as $row) {
                $joomlaUsers[] = [
                    'joomla_id' => $row->joomla_id,
                    'name'      => $row->name,
                    'email'     => $row->email,
                    'group'     => $row->group_name ?? 'Registered',
                ];
            }
        } catch (\Exception $e) {
            $this->command->warn('Could not connect to Joomla database: ' . $e->getMessage());
            $this->command->info('Falling back to empty/stub user array.');
        }

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
