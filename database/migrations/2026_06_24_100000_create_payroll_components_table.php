<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-configurable payroll "frames": allowance titles (Transport, Housing…),
 * deduction titles (Loan, Penalty, Salary Advance…) and statutory contributions
 * (Pension 7%/11%, Provident Fund 5%/12.5%). Definitions only — per-employee
 * amounts live in payroll_component_assignments; statutory ones apply globally.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind');            // App\Enums\PayrollComponentKind: allowance|deduction|statutory
            $table->string('calc_type')->default('fixed'); // App\Enums\PayrollComponentCalc: fixed|percent
            $table->decimal('rate', 8, 4)->nullable();      // % for percent/statutory
            $table->string('side')->nullable();             // App\Enums\ComponentSide: employee|employer (statutory)
            $table->string('applies_to')->default('all');   // all|pension_members|pf_members
            $table->boolean('taxable')->default(true);      // allowances: adds to taxable income
            $table->boolean('exempt_capped')->default(false); // transport-style statutory tax cap
            $table->string('sheet_column')->nullable();     // fixed payslip column this maps to
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);   // seeded core: editable rate/name, not deletable
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
