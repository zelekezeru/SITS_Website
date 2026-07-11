<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee salary loans (distinct from the library book `loans` table).
 *
 * An employee takes a loan of `principal_amount` repaid at a fixed
 * `monthly_amount` deducted from payroll each month until the balance clears.
 * Every deduction (auto payroll or a manual extra/settlement payment) is a row
 * in `employee_loan_payments`; the outstanding balance is principal − payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();            // e.g. LN-2026-0007
            $table->decimal('principal_amount', 12, 2);       // total borrowed
            $table->decimal('monthly_amount', 12, 2);         // fixed monthly deduction
            $table->unsignedSmallInteger('duration_months');  // nominal term (ceil principal / monthly)
            $table->date('start_date');                       // first repayment period
            $table->string('status')->default('active');      // App\Enums\EmployeeLoanStatus
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('employee_loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            // Payroll deductions link to their period; manual payments leave it null.
            $table->foreignId('payroll_period_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('type')->default('payroll');       // App\Enums\EmployeeLoanPaymentType: payroll | manual
            $table->string('note')->nullable();
            $table->date('paid_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // At most one auto payroll deduction per loan per period (manual
            // payments have a null period, which MySQL treats as distinct).
            $table->unique(['employee_loan_id', 'payroll_period_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_payments');
        Schema::dropIfExists('employee_loans');
    }
};
