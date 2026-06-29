<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_description_versions', function (Blueprint $table) {
            // Structured, repeatable JD lines (responsibilities, authorities,
            // qualifications, relationships) — each optionally a KPI. The free
            // text `body` stays as an optional narrative summary.
            $table->json('items')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('job_description_versions', function (Blueprint $table) {
            $table->dropColumn('items');
        });
    }
};
