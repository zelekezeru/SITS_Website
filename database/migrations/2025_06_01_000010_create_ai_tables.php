<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narrative_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('language')->default('en');  // App\Enums\Language
            $table->longText('body');                    // free-text report, any format
            $table->timestamps();
        });

        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('narrative_report_id')->constrained()->cascadeOnDelete();
            $table->string('provider');                  // App\Enums\AiProvider
            $table->string('model')->nullable();
            $table->text('summary_en')->nullable();
            $table->text('summary_am')->nullable();
            $table->json('kpi_scores_json')->nullable(); // per-KPI suggested scores + evidence quotes
            $table->json('sentiment')->nullable();
            $table->json('risk_flags')->nullable();
            $table->boolean('human_confirmed')->default(false);
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Now that narrative_reports exists, wire the deferred FK from migration 000008.
        Schema::table('task_progress_reports', function (Blueprint $table) {
            $table->foreign('narrative_report_id')
                  ->references('id')->on('narrative_reports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_progress_reports', function (Blueprint $table) {
            $table->dropForeign(['narrative_report_id']);
        });
        Schema::dropIfExists('ai_analyses');
        Schema::dropIfExists('narrative_reports');
    }
};
