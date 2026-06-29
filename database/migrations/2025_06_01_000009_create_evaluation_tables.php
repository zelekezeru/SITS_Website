<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // e.g. Q2 2026
            $table->string('cadence');               // App\Enums\Cadence (+ custom)
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open'); // App\Enums\EvaluationPeriodStatus
            $table->string('formula_version')->nullable(); // scoring formula in force (reproducibility)
            $table->timestamps();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('auto_score', 5, 2)->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->decimal('executive_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            // FK to grade_bands added in migration 000011 (table created there)
            $table->unsignedBigInteger('grade_band_id')->nullable()->index();
            $table->string('status')->default('draft'); // App\Enums\EvaluationStatus
            $table->timestamps();
            $table->unique(['employee_id', 'evaluation_period_id']);
        });

        Schema::create('evaluation_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rater_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rater_type');            // App\Enums\RaterType
            $table->foreignId('kpi_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('comment_en')->nullable();
            $table->text('comment_am')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_ratings');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_periods');
    }
};
