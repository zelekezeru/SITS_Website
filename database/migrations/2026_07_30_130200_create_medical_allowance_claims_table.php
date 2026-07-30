<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medical allowance reimbursement claims. Submitted by Finance on an eligible
 * employee's behalf with one or more bill documents (see the polymorphic
 * `documents` table), approved by an admin — which locks in the covered /
 * employee-borne split for that claim — and finally marked paid once Finance
 * disburses the reimbursement.
 *
 * Coverage is tiered and cumulative per `policy_year` (see
 * MedicalAllowanceCalculator): approved + paid claims reserve their slice of
 * the year's full-coverage and coinsurance tiers, so the split an admin locks
 * in always reflects what's actually left of the employee's allowance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_allowance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique(); // e.g. MED-2026-0007
            $table->unsignedSmallInteger('policy_year'); // year the claim counts against for tiered coverage
            $table->decimal('bill_amount', 12, 2); // total medical expense claimed
            $table->decimal('covered_amount', 12, 2)->nullable(); // institution's share — set on approval
            $table->decimal('employee_amount', 12, 2)->nullable(); // employee's own share — set on approval
            $table->date('incident_date')->nullable(); // date of the medical expense/treatment
            $table->string('status')->default('pending_review'); // App\Enums\MedicalAllowanceClaimStatus
            $table->text('notes')->nullable(); // from the requester
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->date('paid_on')->nullable();
            $table->string('payment_reference')->nullable(); // e.g. bank transfer / cheque no.
            // Payroll period this reimbursement is reported under — surfaces it on
            // that period's payslip. Disbursement itself is manual, not payroll-run-gated.
            $table->foreignId('payroll_period_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'policy_year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_allowance_claims');
    }
};
