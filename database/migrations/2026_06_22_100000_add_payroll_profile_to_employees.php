<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Salary grade/level on the payroll sheet, e.g. "G13-L5".
            $table->string('grade')->nullable()->after('base_salary');
            // Provident Fund members (12.5% employer / 5% employee) instead of
            // the public pension scheme. Default false keeps existing behaviour.
            $table->boolean('has_provident_fund')->default(false)->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['grade', 'has_provident_fund']);
        });
    }
};
