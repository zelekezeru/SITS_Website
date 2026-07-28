<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scopes an attendance exemption to a single payroll period.
     *
     * `attendance_exempt` alone stays permanent (management, field staff). With
     * `attendance_exempt_period_id` set, the exemption applies to that one month
     * only — every other period tracks and charges absence as normal.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('attendance_exempt_period_id')
                ->nullable()
                ->after('attendance_exempt_reason')
                ->constrained('payroll_periods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_exempt_period_id');
        });
    }
};
