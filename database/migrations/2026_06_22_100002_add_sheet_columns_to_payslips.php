<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Denormalised "payroll sheet" columns. `payslip_lines` stay the source of
     * truth; these mirror the SITS monthly sheet so the period summary and the
     * PDF/Excel exports render without re-deriving every figure.
     */
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('employee_id');
            $table->string('campus')->nullable()->after('grade');
            $table->decimal('working_days', 5, 2)->default(0)->after('campus');

            $table->decimal('overtime', 12, 2)->default(0)->after('gross');
            $table->decimal('mobile_allowance', 12, 2)->default(0)->after('overtime');
            $table->decimal('transport_allowance', 12, 2)->default(0)->after('mobile_allowance');
            $table->decimal('housing_allowance', 12, 2)->default(0)->after('transport_allowance');
            $table->decimal('cash_allowance', 12, 2)->default(0)->after('housing_allowance');

            $table->decimal('provident_fund_employee', 12, 2)->default(0)->after('employer_pension');
            $table->decimal('provident_fund_employer', 12, 2)->default(0)->after('provident_fund_employee');
            $table->decimal('salary_advance', 12, 2)->default(0)->after('provident_fund_employer');
            $table->decimal('kircha_deduction', 12, 2)->default(0)->after('salary_advance');
            $table->decimal('other_deduction', 12, 2)->default(0)->after('kircha_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'grade', 'campus', 'working_days',
                'overtime', 'mobile_allowance', 'transport_allowance',
                'housing_allowance', 'cash_allowance',
                'provident_fund_employee', 'provident_fund_employer',
                'salary_advance', 'kircha_deduction', 'other_deduction',
            ]);
        });
    }
};
