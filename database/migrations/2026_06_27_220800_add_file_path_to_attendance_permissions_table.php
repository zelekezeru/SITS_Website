<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_permissions', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_permissions', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
