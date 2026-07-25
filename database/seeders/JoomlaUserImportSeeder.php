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

    /**
     * Weight of Laravel roles for resolving conflicts when a user belongs to multiple groups.
     */
    protected array $roleWeights = [
        'SUPERADMIN' => 10,
        'ADMIN'      => 8,
        'EDITOR'     => 5,
        'STUDENT'    => 1,
    ];

    public function run(): void
    {
        $joomlaUsers = [];
        try {
            \Illuminate\Support\Facades\DB::connection('joomla')->getPdo();

            // Auto-discover the correct table prefix by finding a *_users table
            $tables = \Illuminate\Support\Facades\DB::connection('joomla')
                ->select("SHOW TABLES");
            $tableNames = array_map(fn($t) => array_values((array)$t)[0], $tables);

            $configuredPrefix = config('database.connections.joomla.prefix', 'vxgtm_');
            $prefix = $configuredPrefix;

            // Try to find the users table: check configured prefix first, then discover
            $usersTable = $prefix . 'users';
            if (!in_array($usersTable, $tableNames)) {
                // Find any table ending in _users
                $found = array_filter($tableNames, fn($t) => str_ends_with($t, '_users'));
                if (!empty($found)) {
                    $firstUsersTable = array_values($found)[0];
                    $prefix = substr($firstUsersTable, 0, strrpos($firstUsersTable, '_users') + 1);
                    $this->command->info("Discovered Joomla table prefix: '{$prefix}' (found table: {$firstUsersTable})");
                } else {
                    $this->command->warn("Could not find any *_users table. Available tables: " . implode(', ', $tableNames));
                    throw new \RuntimeException("No users table found in Joomla database.");
                }
            } else {
                $this->command->info("Using Joomla table prefix: '{$prefix}'");
            }

            $rows = \Illuminate\Support\Facades\DB::connection('joomla')->select("
                SELECT u.id as joomla_id, u.name, u.email, g.title as group_name
                FROM {$prefix}users u
                LEFT JOIN {$prefix}user_usergroup_map m ON u.id = m.user_id
                LEFT JOIN {$prefix}usergroups g ON m.group_id = g.id
                WHERE u.block = 0
            ");

            $this->command->info("Found " . count($rows) . " Joomla user records.");

            foreach ($rows as $row) {
                $email = trim($row->email);
                if (empty($email)) {
                    continue;
                }

                $roleName = $this->roleMap[$row->group_name ?? 'Registered'] ?? 'STUDENT';

                if (isset($joomlaUsers[$email])) {
                    $currentWeight = $this->roleWeights[$joomlaUsers[$email]['role']] ?? 0;
                    $newWeight = $this->roleWeights[$roleName] ?? 0;
                    if ($newWeight > $currentWeight) {
                        $joomlaUsers[$email]['role'] = $roleName;
                    }
                } else {
                    $joomlaUsers[$email] = [
                        'name'  => $row->name,
                        'email' => $email,
                        'role'  => $roleName,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->command->warn('Could not connect to Joomla database: ' . $e->getMessage());
            $this->command->info('Falling back to empty/stub user array.');
        }

        $defaultPassword = Hash::make('ChangeMe@2026');
        $imported = 0;
        $skipped  = 0;

        foreach ($joomlaUsers as $email => $data) {
            // Skip if email already exists
            if (User::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $email,
                'password' => $defaultPassword,
                'role'     => $data['role'],
                'is_approved' => true,
                'is_active'   => true,
                'password_changed' => false,
            ]);

            $role = Role::where('name', $data['role'])->first();
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
