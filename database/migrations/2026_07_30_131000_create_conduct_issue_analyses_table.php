<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated storage for AiAnalysisContract::analyzeConductIssue() results.
 *
 * AnalyzeConductIssueJob previously wrote to ai_analyses using columns
 * (analysable_type, analysable_id, result, ...) that table never had —
 * every run failed. Conduct analysis has its own shape (severity_assessment,
 * risk_level, suggested_actions, ...) that doesn't fit the narrative/
 * performance columns on ai_analyses, so — matching the codebase's existing
 * per-domain-table pattern (NarrativeReport -> ai_analyses) rather than
 * introducing polymorphism this codebase doesn't otherwise use — this gives
 * ConductIssue its own analysis table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conduct_issue_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conduct_issue_id')->constrained()->cascadeOnDelete();
            $table->string('provider');   // App\Enums\AiProvider
            $table->string('model')->nullable();

            $table->string('severity_assessment')->nullable(); // minor|moderate|major|critical
            $table->decimal('confidence', 3, 2)->nullable();    // 0.00–1.00
            $table->string('risk_level')->nullable();           // low|medium|high|critical
            $table->json('suggested_actions')->nullable();
            $table->boolean('escalation_needed')->default(false);
            $table->boolean('investigation_required')->default(false);
            $table->json('warnings')->nullable();

            $table->boolean('human_confirmed')->default(false);
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['conduct_issue_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduct_issue_analyses');
    }
};
