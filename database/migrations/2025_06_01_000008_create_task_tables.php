<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();          // primary owner
            $table->foreignId('target_id')->nullable()->constrained()->nullOnDelete();    // strategic link
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete(); // subtasks
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cadence')->default('fortnightly'); // App\Enums\Cadence
            $table->date('starting_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->string('status')->default('pending');      // App\Enums\TaskStatus
            $table->decimal('completion_pct', 5, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();   // drives policy
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // Contribution pivot: a task contributes toward KPIs without either owning the other.
        Schema::create('kpi_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->decimal('contribution_weight', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['kpi_id', 'task_id']);
        });

        Schema::create('fortnight_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fortnight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['fortnight_id', 'task_id']);
        });

        Schema::create('day_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['day_id', 'task_id']);
        });

        Schema::create('department_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['department_id', 'task_id']);
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        // Threaded discussion lives in the polymorphic `comments` table (migration 000013).

        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fortnight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('deadline')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('task_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->decimal('completion_pct', 5, 2)->default(0);
            // FK constraint to narrative_reports added in migration 000010 (table created there)
            $table->unsignedBigInteger('narrative_report_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_progress_reports');
        Schema::dropIfExists('deliverables');
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('department_task');
        Schema::dropIfExists('day_task');
        Schema::dropIfExists('fortnight_task');
        Schema::dropIfExists('kpi_task');
        Schema::dropIfExists('tasks');
    }
};
