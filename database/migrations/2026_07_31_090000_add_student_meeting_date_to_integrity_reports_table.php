<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7's review form wants an optional student-meeting date alongside
 * notes and the decision — missing from the original Phase 1 schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrity_reports', function (Blueprint $table) {
            $table->date('student_meeting_date')->nullable()->after('review_notes');
        });
    }

    public function down(): void
    {
        Schema::table('integrity_reports', function (Blueprint $table) {
            $table->dropColumn('student_meeting_date');
        });
    }
};
