<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The sheet's "Absent Days" was derived live from the attendance record at
     * render time while the money beside it came from the payslip. If attendance
     * changed after a run the two disagreed. Store the days the run actually
     * charged — post-permission and post-grace — so both read from the same run.
     */
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedSmallInteger('absent_days')->nullable()->after('working_days');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('absent_days');
        });
    }
};
