<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ImportMoodleUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moodle:import-users {--config= : Path to Moodle config.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users and instructors from Moodle database into SITS users table';

    /**
     * Mock DB Connection for testing.
     */
    public static ?\PDO $dbConnectionMock = null;

    /**
     * Weight of Laravel roles for resolving conflicts when a user has multiple Moodle roles.
     */
    protected array $roleWeights = [
        'ADMIN'      => 10,
        'TRAINER'    => 5,
        'STUDENT'    => 1,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $configPath = $this->option('config');

        if (!$configPath) {
            $home = env('HOME') ?: ($_SERVER['HOME'] ?? '');
            if (empty($home)) {
                $home = dirname(base_path());
            }
            $configPath = $this->firstExisting([
                "$home/moodle/config.php",
                "$home/moodle/public/config.php",
                "$home/moodle-old/config.php",
                "$home/moodle-old/public/config.php",
            ]);
        }

        if (!$configPath || !file_exists($configPath)) {
            $this->error("Moodle config.php not found. Please provide path using --config=PATH.");
            return 1;
        }

        $this->info("Parsing Moodle config from: $configPath");
        $moodleConfig = $this->parseMoodleConfig($configPath);

        if (!$moodleConfig || empty($moodleConfig['dbname'])) {
            $this->error("Failed to parse database configuration from Moodle config.php.");
            return 1;
        }

        $this->info("Connecting to Moodle database: {$moodleConfig['dbname']}@{$moodleConfig['dbhost']}");
        try {
            $pdo = $this->getDbConnection($moodleConfig);
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            return 1;
        }

        $prefix = $moodleConfig['prefix'] ?? 'mdl_';

        $this->info("Fetching Moodle users...");
        $stmt = $pdo->prepare("SELECT id, username, firstname, lastname, email FROM {$prefix}user WHERE deleted = 0 AND username != 'guest' AND id > 2");
        $stmt->execute();
        $moodleUsers = $stmt->fetchAll();

        if (empty($moodleUsers)) {
            $this->warn("No active users found in Moodle user table.");
            return 0;
        }

        $this->info("Fetching Moodle user role assignments...");
        $rolesStmt = $pdo->prepare("
            SELECT DISTINCT u.email, r.shortname
            FROM {$prefix}user u
            JOIN {$prefix}role_assignments ra ON ra.userid = u.id
            JOIN {$prefix}role r ON r.id = ra.roleid
            WHERE u.deleted = 0 AND u.username != 'guest'
        ");
        $rolesStmt->execute();
        
        $roleAssignments = [];
        foreach ($rolesStmt->fetchAll() as $row) {
            $email = strtolower(trim($row['email']));
            if (!empty($email)) {
                $roleAssignments[$email][] = $row['shortname'];
            }
        }

        $defaultPassword = Hash::make('ChangeMe@2026');
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($moodleUsers as $row) {
            $email = strtolower(trim($row['email']));
            if (empty($email)) {
                continue;
            }

            // Determine target SITS role
            $userRoles = $roleAssignments[$email] ?? [];
            $targetRole = 'STUDENT';

            $isAdmin = false;
            $isTrainer = false;

            foreach ($userRoles as $r) {
                if ($r === 'admin') {
                    $isAdmin = true;
                }
                if (in_array($r, ['editingteacher', 'teacher', 'coursecreator', 'manager'])) {
                    $isTrainer = true;
                }
            }

            if ($isAdmin) {
                $targetRole = 'ADMIN';
            } elseif ($isTrainer) {
                $targetRole = 'TRAINER';
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                $name = trim($row['firstname'] . ' ' . $row['lastname']);
                if (empty($name)) {
                    $name = $row['username'];
                }

                $user = User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'password'          => $defaultPassword,
                    'role'              => $targetRole,
                    'is_approved'       => true,
                    'is_active'         => true,
                    'password_changed'  => false,
                    'default_password'  => 'ChangeMe@2026',
                ]);
                $imported++;
            } else {
                $skipped++;
            }

            // Assign role using Spatie Permissions
            $spatieRole = Role::firstOrCreate(['name' => $targetRole]);
            
            // Check if existing user needs role upgrade
            if ($user) {
                $currentHighestRole = 'STUDENT';
                foreach ($user->getRoleNames() as $existingRoleName) {
                    $currentWeight = $this->roleWeights[$existingRoleName] ?? 0;
                    $targetWeight = $this->roleWeights[$targetRole] ?? 0;
                    if ($currentWeight > $targetWeight) {
                        $targetRole = $existingRoleName; // Keep the higher role
                    }
                }
                
                if (!$user->hasRole($targetRole)) {
                    $user->assignRole($targetRole);
                    $updated++;
                }
            }
        }

        $this->info("Import complete. Imported: $imported, Updated Roles: $updated, Skipped (already exist): $skipped.");
        return 0;
    }

    /**
     * Parse Moodle config.php into structured array.
     */
    protected function parseMoodleConfig($path)
    {
        $src = file_get_contents($path);
        $g = function ($key, $default = '') use ($src) {
            if (preg_match('/\$CFG->' . preg_quote($key, '/') . '\s*=\s*[\'"]([^\'"]*)[\'"]/', $src, $m)) {
                return $m[1];
            }
            return $default;
        };

        return [
            'dbtype'  => $g('dbtype', 'mariadb'),
            'dbhost'  => $g('dbhost', 'localhost'),
            'dbname'  => $g('dbname'),
            'dbuser'  => $g('dbuser'),
            'dbpass'  => $g('dbpass'),
            'prefix'  => $g('prefix', 'mdl_'),
        ];
    }

    protected function getDbConnection(array $config)
    {
        if (self::$dbConnectionMock !== null) {
            return self::$dbConnectionMock;
        }

        $dsn = "mysql:host={$config['dbhost']};dbname={$config['dbname']};charset=utf8mb4";
        return new \PDO($dsn, $config['dbuser'], $config['dbpass'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Find first existing path from array.
     */
    protected function firstExisting(array $paths)
    {
        foreach ($paths as $p) {
            if ($p && file_exists($p)) {
                return $p;
            }
        }
        return null;
    }
}
