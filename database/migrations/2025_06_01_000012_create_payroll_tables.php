<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // e.g. June 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open'); // App\Enums\PayrollStatus
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('manual'); // App\Enums\AttendanceSource
            $table->decimal('work_hours', 8, 2)->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedSmallInteger('permitted_days')->default(0); // approved leave
            $table->decimal('overtime_normal', 8, 2)->default(0); // 1.5x
            $table->decimal('ot_night', 8, 2)->default(0);
            $table->decimal('ot_rest', 8, 2)->default(0);         // 2.0x
            $table->decimal('ot_holiday', 8, 2)->default(0);      // 2.5x
            $table->string('status')->default('raw');             // App\Enums\AttendanceStatus
            $table->timestamps();
            $table->unique(['employee_id', 'payroll_period_id']);
        });

        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_income', 12, 2);
            $table->decimal('max_income', 12, 2)->nullable(); // null = top band
            $table->decimal('rate', 5, 2);                     // %
            $table->decimal('deduction', 12, 2)->default(0);   // quick-deduction constant
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('taxable_income', 12, 2)->default(0);
            $table->decimal('income_tax', 12, 2)->default(0);
            $table->decimal('employee_pension', 12, 2)->default(0);
            $table->decimal('employer_pension', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->string('status')->default('draft'); // App\Enums\PayslipStatus
            $table->timestamps();
            $table->unique(['employee_id', 'payroll_period_id']);
        });

        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->string('type');   // earning|deduction
            $table->string('label');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        // Wire the deferred FK from migration 000011.
        Schema::table('increment_recommendations', function (Blueprint $table) {
            $table->foreign('applied_payroll_period_id')
                  ->references('id')->on('payroll_periods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('increment_recommendations', function (Blueprint $table) {
            $table->dropForeign(['applied_payroll_period_id']);
        });
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('payroll_periods');
    }
};
