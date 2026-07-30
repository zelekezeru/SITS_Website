<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Web-source matches (Claude web search tool, Phase 4.5) are a separate,
 * explicitly-triggered check from internal-corpus matching and are never
 * blended into `overall_similarity` — hence a dedicated nullable column
 * rather than reusing the corpus similarity field.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plagiarism_reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('web_similarity')->nullable()->after('overall_similarity');
        });
    }

    public function down(): void
    {
        Schema::table('plagiarism_reports', function (Blueprint $table) {
            $table->dropColumn('web_similarity');
        });
    }
};
