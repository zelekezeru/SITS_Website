<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('status')->default('pending_review'); // App\Enums\AttendanceImportStatus
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('ambiguous_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_import_id')->constrained()->cascadeOnDelete();

            // Raw device fields, kept verbatim for audit/context.
            $table->string('device_employee_code');
            $table->string('device_name');
            $table->string('device_department')->nullable();
            $table->unsignedInteger('work_duration_standard_minutes')->default(0);
            $table->unsignedInteger('work_duration_actual_minutes')->default(0);
            $table->unsignedInteger('late_times')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('leave_early_times')->default(0);
            $table->unsignedInteger('leave_early_minutes')->default(0);
            $table->unsignedInteger('overtime_normal_minutes')->default(0);
            $table->unsignedInteger('overtime_special_minutes')->default(0);
            $table->unsignedInteger('lack_times')->default(0);
            $table->unsignedInteger('lack_minutes')->default(0);
            $table->unsignedInteger('attendance_days_standard')->default(0);
            $table->unsignedInteger('attendance_days_actual')->default(0);
            $table->unsignedInteger('absent_days')->default(0);
            $table->string('remarks')->nullable();

            // Resolution against the system's Employee directory.
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('match_status')->default('unmatched'); // App\Enums\AttendanceRowMatchStatus
            $table->string('match_method')->nullable(); // App\Enums\AttendanceRowMatchMethod
            $table->decimal('match_confidence', 5, 2)->nullable();

            $table->boolean('is_excluded')->default(false);
            $table->string('exclusion_reason')->nullable();
            $table->unsignedSmallInteger('suggested_permitted_days')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_rows');
        Schema::dropIfExists('attendance_imports');
    }
};
