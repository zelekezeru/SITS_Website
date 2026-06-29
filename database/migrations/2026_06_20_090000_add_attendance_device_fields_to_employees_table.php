<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('device_employee_code')->nullable()->unique()->after('staff_no');
            $table->boolean('attendance_exempt')->default(false)->after('is_active');
            $table->string('attendance_exempt_reason')->nullable()->after('attendance_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['device_employee_code', 'attendance_exempt', 'attendance_exempt_reason']);
        });
    }
};
