<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Only employees enrolled here (and full-time) are eligible to submit
            // medical allowance claims — see MedicalAllowanceClaim.
            $table->boolean('medical_allowance_enabled')->default(false)->after('statutory_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('medical_allowance_enabled');
        });
    }
};
