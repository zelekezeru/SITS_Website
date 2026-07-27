<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('SUPERADMIN');
        Role::findOrCreate('ADMIN');
        Role::findOrCreate('STUDENT');
    }

    public function test_user_without_risky_data_is_soft_deleted_and_email_freed_for_reuse()
    {
        $admin = User::factory()->create([
            'email' => 'admin_test@sits.edu.et',
            'role' => 'ADMIN',
        ]);
        $admin->assignRole('ADMIN');

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'role' => 'STUDENT',
        ]);

        $originalEmail = $user->email;
        $userId = $user->id;

        // Perform soft delete as Admin
        $response = $this->actingAs($admin)->delete(route('users.destroy', $user->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert user is soft deleted in database
        $this->assertSoftDeleted('users', ['id' => $userId]);

        // Assert soft deleted user's email was renamed to name_deleted_id@sits.edu.et
        $deletedUser = User::withTrashed()->find($userId);
        $this->assertEquals("johndoe_deleted_{$userId}@sits.edu.et", $deletedUser->email);
        $this->assertFalse($deletedUser->is_active);

        // Assert original email is now completely reusable for a new user
        $newUser = User::create([
            'name' => 'New John Doe',
            'email' => $originalEmail,
            'password' => bcrypt('password'),
            'role' => 'STUDENT',
        ]);

        $this->assertNotNull($newUser);
        $this->assertEquals($originalEmail, $newUser->email);
    }

    public function test_user_with_risky_data_cannot_be_deleted()
    {
        $admin = User::factory()->create([
            'email' => 'admin_test2@sits.edu.et',
            'role' => 'ADMIN',
        ]);
        $admin->assignRole('ADMIN');

        // Create user with attached risky employee record
        $employeeUser = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => 'STUDENT',
        ]);

        Employee::create([
            'user_id' => $employeeUser->id,
            'staff_no' => 'EMP-001',
            'full_name_en' => 'Employee Test',
        ]);

        // Attempt soft delete
        $response = $this->actingAs($admin)->delete(route('users.destroy', $employeeUser->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Assert user was NOT deleted
        $this->assertDatabaseHas('users', [
            'id' => $employeeUser->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_restore_soft_deleted_user()
    {
        $admin = User::factory()->create([
            'email' => 'admin_test3@sits.edu.et',
            'role' => 'ADMIN',
        ]);
        $admin->assignRole('ADMIN');

        $user = User::factory()->create([
            'email' => 'to_restore@example.com',
            'role' => 'STUDENT',
        ]);

        $userId = $user->id;
        $user->safelySoftDelete();

        $this->assertSoftDeleted('users', ['id' => $userId]);

        // Admin restores user
        $response = $this->actingAs($admin)->post(route('users.restore', $userId));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $restoredUser = User::find($userId);
        $this->assertNotNull($restoredUser);
        $this->assertNull($restoredUser->deleted_at);
        $this->assertTrue($restoredUser->is_active);
    }
}
