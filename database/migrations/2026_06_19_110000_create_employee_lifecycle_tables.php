<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Give employees a canonical current status (EmployeeStatus enum). The
        // status_changes table below records the history of transitions; this
        // column holds the current value (is_active stays as a fast boolean flag).
        Schema::table('employees', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active'); // EmployeeStatus enum
            $table->index('status');
        });

        Schema::create('employee_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('from_status');                      // EmployeeStatus enum
            $table->string('to_status');                        // EmployeeStatus enum
            $table->string('reason')->nullable();               // conduct_decision, termination, manual…
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('effective_date');
            $table->dateTime('changed_at');
            $table->nullableMorphs('reference');                // conduct_decision / termination / leave_request
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('from_status');
            $table->index('to_status');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('leave_type');                       // LeaveType enum
            $table->string('status')->default('submitted');     // LeaveStatus enum
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested');
            $table->integer('days_approved')->nullable();
            $table->text('reason');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });

        Schema::create('terminations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('reason');                           // TerminationReason enum
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');       // pending, finalized, archived
            $table->date('effective_date');

            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('initiated_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();

            $table->decimal('severance_amount', 12, 2)->nullable();
            $table->text('severance_notes')->nullable();
            $table->foreignId('final_payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->json('handover_checklist')->nullable();     // {tasks: bool, equipment: bool, documents: bool}
            $table->dateTime('handover_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('status');
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminations');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_status_changes');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
