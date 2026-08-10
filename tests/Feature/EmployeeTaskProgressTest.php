<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The inline "Done" / "Reopen" buttons on Employee → My Tasks post to the
 * lightweight progress endpoint instead of round-tripping the edit modal.
 * What matters: an employee can close their own task in one click, cannot
 * touch anybody else's, and cannot set a status the self-service flow does
 * not offer — Missed stays a manager-side call.
 */
class EmployeeTaskProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_mark_their_own_task_done(): void
    {
        [$user, $employee] = $this->employee();
        $task = $this->task($employee);

        $this->actingAs($user)
            ->post("/dashboard/tasks/{$task->id}/progress", [
                'status' => TaskStatus::Completed->value,
                'completion_pct' => 100,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertEquals(100.0, (float) $task->completion_pct);
    }

    /** Reopening keeps the recorded percentage — only the status moves back. */
    public function test_employee_can_reopen_a_completed_task(): void
    {
        [$user, $employee] = $this->employee();
        $task = $this->task($employee, [
            'status' => TaskStatus::Completed->value,
            'completion_pct' => 100,
        ]);

        $this->actingAs($user)
            ->post("/dashboard/tasks/{$task->id}/progress", [
                'status' => TaskStatus::InProgress->value,
                'completion_pct' => 100,
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertEquals(100.0, (float) $task->completion_pct);
    }

    public function test_employee_cannot_progress_someone_elses_task(): void
    {
        [, $owner] = $this->employee();
        [$intruder] = $this->employee();
        $task = $this->task($owner);

        $this->actingAs($intruder)
            ->post("/dashboard/tasks/{$task->id}/progress", [
                'status' => TaskStatus::Completed->value,
                'completion_pct' => 100,
            ])
            ->assertForbidden();

        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
    }

    /** Missed is not in the self-service status list, so it must not slip through. */
    public function test_manager_only_status_is_rejected(): void
    {
        [$user, $employee] = $this->employee();
        $task = $this->task($employee);

        $this->actingAs($user)
            ->post("/dashboard/tasks/{$task->id}/progress", [
                'status' => TaskStatus::Missed->value,
                'completion_pct' => 0,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
    }

    public function test_completion_percentage_is_bounded(): void
    {
        [$user, $employee] = $this->employee();
        $task = $this->task($employee);

        $this->actingAs($user)
            ->post("/dashboard/tasks/{$task->id}/progress", [
                'status' => TaskStatus::Completed->value,
                'completion_pct' => 140,
            ])
            ->assertSessionHasErrors('completion_pct');
    }

    // ===================== HELPERS =====================

    /** @return array{0: User, 1: Employee} */
    private function employee(): array
    {
        $user = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'staff_no' => 'EMP-'.$user->id,
            'full_name_en' => 'Task Test Employee '.$user->id,
            'base_salary' => 15000,
            'legal_daily_hour_limit' => 8,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);

        return [$user, $employee];
    }

    private function task(Employee $employee, array $attributes = []): Task
    {
        return Task::create($attributes + [
            'employee_id' => $employee->id,
            'title' => 'Draft fortnight progress report',
            'cadence' => 'fortnightly',
            'weight' => 1,
            'status' => TaskStatus::Pending->value,
            'completion_pct' => 0,
        ]);
    }
}
