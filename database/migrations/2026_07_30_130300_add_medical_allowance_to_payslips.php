<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Non-taxable medical reimbursements paid out and attributed to this
            // period — added straight to net pay, never to gross/taxable income.
            $table->decimal('medical_allowance', 12, 2)->default(0)->after('loan_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('medical_allowance');
        });
    }
};
