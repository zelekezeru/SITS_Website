<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_analyses was keyed on (narrative_report_id, provider) only, so the light
 * narrative analysis and the deep performance analysis — both written by
 * separate jobs for the same report/provider — silently overwrote each other
 * on updateOrCreate. This column lets both coexist.
 *
 * Backfill defaults existing rows to 'narrative': content can't be
 * retroactively classified, but at most one row per (narrative_report_id,
 * provider) could exist under the old bug, so the unique index below is safe
 * to add regardless of which type they actually were.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->string('analysis_type')->default('narrative')->after('provider');
        });

        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->unique(['narrative_report_id', 'provider', 'analysis_type'], 'ai_analyses_report_provider_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->dropUnique('ai_analyses_report_provider_type_unique');
            $table->dropColumn('analysis_type');
        });
    }
};
