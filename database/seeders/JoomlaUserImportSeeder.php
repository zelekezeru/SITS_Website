<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * JoomlaUserImportSeeder
 *
 * Imports Joomla and Moodle user accounts from SQL dump files directly into the Laravel users table,
 * preserving existing password hashes so users can log in immediately with their existing credentials.
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
        $dumpFiles = [
            'c:/Users/hp/Downloads/sitseduorg_joomla.sql',
            'c:/Users/hp/Downloads/sitseduorg_jo749sb.sql',
            'c:/Users/hp/Downloads/sitseduorg_moodle.sql',
        ];

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;

        $parsedUsers = [];

        foreach ($dumpFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $this->command?->info("Processing user dump file: " . basename($file));
            $content = file_get_contents($file);

            // 1. Parse usergroups (if present)
            $groups = [];
            if (preg_match_all('/INSERT INTO [`"]?josn9_usergroups[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= 3) {
                            $groups[trim($v[0])] = trim($v[2]); // id => title
                        }
                    }
                }
            }

            // 2. Parse user-group mapping
            $userGroupMap = [];
            if (preg_match_all('/INSERT INTO [`"]?josn9_user_usergroup_map[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= 2) {
                            $uId = trim($v[0]);
                            $gId = trim($v[1]);
                            $gTitle = $groups[$gId] ?? 'Registered';
                            $userGroupMap[$uId][$gTitle] = true;
                        }
                    }
                }
            }

            // 3. Parse Joomla Users (josn9_users)
            if (preg_match_all('/INSERT INTO [`"]?josn9_users[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols = array_map(fn($c) => trim(str_replace('`', '', $c)), explode(',', $m[1]));
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= count($cols)) {
                            $row = array_combine(array_slice($cols, 0, count($v)), $v);
                            $email = strtolower(trim($row['email'] ?? ''));
                            if (empty($email)) continue;

                            $uGroups = array_keys($userGroupMap[$row['id']] ?? ['Registered' => true]);
                            
                            // Determine highest role
                            $bestRole = 'STUDENT';
                            $bestWeight = 0;
                            foreach ($uGroups as $g) {
                                $rName = $this->roleMap[$g] ?? 'STUDENT';
                                $w = $this->roleWeights[$rName] ?? 0;
                                if ($w > $bestWeight) {
                                    $bestWeight = $w;
                                    $bestRole = $rName;
                                }
                            }

                            $parsedUsers[$email] = [
                                'name'     => trim($row['name'] ?? $row['username']),
                                'email'    => $email,
                                'password' => trim($row['password']),
                                'role'     => $bestRole,
                            ];
                        }
                    }
                }
            }

            // 4. Parse Moodle Users (mdl_user)
            if (preg_match_all('/INSERT INTO [`"]?mdl_user[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols = array_map(fn($c) => trim(str_replace('`', '', $c)), explode(',', $m[1]));
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= count($cols)) {
                            $row = array_combine(array_slice($cols, 0, count($v)), $v);
                            $email = strtolower(trim($row['email'] ?? ''));
                            $username = trim($row['username'] ?? '');

                            if (empty($email) || $username === 'guest' || !empty($row['deleted'])) {
                                continue;
                            }

                            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                            if (empty($name)) $name = $username;

                            $role = ($username === 'admin') ? 'SUPERADMIN' : 'STUDENT';

                            if (!isset($parsedUsers[$email])) {
                                $parsedUsers[$email] = [
                                    'name'     => $name,
                                    'email'    => $email,
                                    'password' => trim($row['password']),
                                    'role'     => $role,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $this->command?->info("Parsed total of " . count($parsedUsers) . " unique users from SQL dumps.");

        // Insert/Update into Laravel database
        foreach ($parsedUsers as $email => $data) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Direct DB insert or create to preserve the EXACT password hash ($2y$10$...)
                $userId = DB::table('users')->insertGetId([
                    'name'              => $data['name'],
                    'email'             => $email,
                    'password'          => $data['password'],
                    'role'              => $data['role'],
                    'is_approved'       => true,
                    'is_active'         => true,
                    'password_changed'  => true,
                    'email_verified_at' => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                $user = User::find($userId);
                if ($user) {
                    $user->assignRole($data['role']);
                }

                $imported++;
            } else {
                // Update password hash if needed and sync role
                DB::table('users')->where('id', $user->id)->update([
                    'password' => $data['password'],
                ]);

                // Upgrade role if higher weight
                $currentWeight = $this->roleWeights[$user->role] ?? 0;
                $newWeight     = $this->roleWeights[$data['role']] ?? 0;

                if ($newWeight > $currentWeight) {
                    $user->role = $data['role'];
                    $user->save();
                }

                $user->assignRole($user->role);
                $updated++;
            }
        }

        $this->command?->info("User Migration Complete: {$imported} imported, {$updated} updated/synced, {$skipped} skipped.");
    }
}
