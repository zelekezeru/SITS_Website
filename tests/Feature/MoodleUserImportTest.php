<?php

namespace Tests\Feature;

use App\Models\User;
use App\Console\Commands\ImportMoodleUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MoodleUserImportTest extends TestCase
{
    use RefreshDatabase;

    protected string $tempConfigPath;
    protected static \PDO $sharedTestPdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure target roles exist in the database
        Role::findOrCreate('SUPERADMIN');
        Role::findOrCreate('ADMIN');
        Role::findOrCreate('TRAINER');
        Role::findOrCreate('STUDENT');

        // Create a temporary mock config.php
        $this->tempConfigPath = tempnam(sys_get_temp_dir(), 'mcfg_') . '.php';
        $mockConfigContent = '<?php
$CFG = new stdClass();
$CFG->dbtype = "sqlite";
$CFG->dbhost = "localhost";
$CFG->dbname = "test_moodle";
$CFG->dbuser = "root";
$CFG->dbpass = "password";
$CFG->prefix = "mdl_";
';
        file_put_contents($this->tempConfigPath, $mockConfigContent);

        // Initialize shared memory DB PDO connection
        self::$sharedTestPdo = new \PDO('sqlite::memory:', null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        // Create Moodle schema in mock database
        self::$sharedTestPdo->exec("
            CREATE TABLE mdl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(100),
                firstname VARCHAR(100),
                lastname VARCHAR(100),
                email VARCHAR(100),
                deleted INTEGER DEFAULT 0,
                suspended INTEGER DEFAULT 0
            );
            CREATE TABLE mdl_role (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                shortname VARCHAR(100)
            );
            CREATE TABLE mdl_role_assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                userid INTEGER,
                roleid INTEGER,
                contextid INTEGER
            );
        ");

        // Set the static mock connection on the command class
        ImportMoodleUsers::$dbConnectionMock = self::$sharedTestPdo;
    }

    public static function getTestPdo()
    {
        return self::$sharedTestPdo;
    }

    protected function tearDown(): void
    {
        ImportMoodleUsers::$dbConnectionMock = null;
        if (file_exists($this->tempConfigPath)) {
            @unlink($this->tempConfigPath);
        }
        parent::tearDown();
    }

    public function test_it_imports_moodle_users_and_maps_roles_correctly()
    {
        // 1. Populate mock Moodle database with roles
        $pdo = self::$sharedTestPdo;
        
        $pdo->exec("INSERT INTO mdl_role (id, shortname) VALUES (1, 'admin')");
        $pdo->exec("INSERT INTO mdl_role (id, shortname) VALUES (2, 'editingteacher')");
        $pdo->exec("INSERT INTO mdl_role (id, shortname) VALUES (3, 'student')");

        // 2. Populate mock Moodle database with users (id > 2)
        // User 3: student
        $pdo->exec("INSERT INTO mdl_user (id, username, firstname, lastname, email) VALUES (3, 'student1', 'Abebe', 'Bikila', 'abebe@example.com')");
        $pdo->exec("INSERT INTO mdl_role_assignments (userid, roleid, contextid) VALUES (3, 3, 1)");

        // User 4: instructor (editingteacher)
        $pdo->exec("INSERT INTO mdl_user (id, username, firstname, lastname, email) VALUES (4, 'teacher1', 'Dr. Kebede', 'Balcha', 'kebede@example.com')");
        $pdo->exec("INSERT INTO mdl_role_assignments (userid, roleid, contextid) VALUES (4, 2, 1)");

        // User 5: admin
        $pdo->exec("INSERT INTO mdl_user (id, username, firstname, lastname, email) VALUES (5, 'admin1', 'Super', 'User', 'admin@example.com')");
        $pdo->exec("INSERT INTO mdl_role_assignments (userid, roleid, contextid) VALUES (5, 1, 1)");

        // 3. Run the Artisan command pointing to the temporary mock config
        $exitCode = Artisan::call('moodle:import-users', [
            '--config' => $this->tempConfigPath
        ]);

        if ($exitCode !== 0) {
            echo "\nArtisan Command Output:\n" . Artisan::output() . "\n";
        }

        $this->assertEquals(0, $exitCode);

        // 4. Assert users were created in Laravel with the correct roles
        $abebe = User::where('email', 'abebe@example.com')->first();
        $this->assertNotNull($abebe);
        $this->assertEquals('Abebe Bikila', $abebe->name);
        $this->assertTrue($abebe->hasRole('STUDENT'));
        $this->assertFalse($abebe->hasRole('TRAINER'));

        $kebede = User::where('email', 'kebede@example.com')->first();
        $this->assertNotNull($kebede);
        $this->assertEquals('Dr. Kebede Balcha', $kebede->name);
        $this->assertTrue($kebede->hasRole('TRAINER'));
        $this->assertFalse($kebede->hasRole('STUDENT'));

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('ADMIN'));
    }

    public function test_it_does_not_duplicate_existing_users()
    {
        // Setup pre-existing user in Laravel
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'secret',
            'is_approved' => true,
            'is_active' => true,
            'role' => 'STUDENT',
        ])->assignRole('STUDENT');

        $pdo = self::$sharedTestPdo;
        $pdo->exec("INSERT INTO mdl_role (id, shortname) VALUES (2, 'editingteacher')");
        $pdo->exec("INSERT INTO mdl_user (id, username, firstname, lastname, email) VALUES (3, 'existing_moodle', 'Existing', 'User', 'existing@example.com')");
        $pdo->exec("INSERT INTO mdl_role_assignments (userid, roleid, contextid) VALUES (3, 2, 1)"); // Trainer in Moodle

        $exitCode = Artisan::call('moodle:import-users', [
            '--config' => $this->tempConfigPath
        ]);

        if ($exitCode !== 0) {
            echo "\nArtisan Command Output (test 2):\n" . Artisan::output() . "\n";
        }

        $this->assertEquals(0, $exitCode);

        // Check user wasn't duplicated (only 1 exists)
        $users = User::where('email', 'existing@example.com')->get();
        $this->assertCount(1, $users);

        // Check role was updated/assigned according to Moodle (upgraded to TRAINER)
        $user = $users->first();
        $this->assertTrue($user->hasRole('TRAINER'));
    }
}
