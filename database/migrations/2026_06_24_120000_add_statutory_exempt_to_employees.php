<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Staff on neither the pension nor the provident-fund scheme (e.g. some
            // contract instructors) — no statutory contributions apply to them.
            $table->boolean('statutory_exempt')->default(false)->after('has_provident_fund');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('statutory_exempt');
        });
    }
};
