<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attaches an allowance/deduction component to an employee with a schedule:
 * monthly (recurring), a month range, or one-time for a single period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_component_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('schedule_type')->default('monthly'); // App\Enums\ScheduleType
            $table->foreignId('start_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->foreignId('end_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'payroll_component_id'], 'pca_employee_component_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_component_assignments');
    }
};
