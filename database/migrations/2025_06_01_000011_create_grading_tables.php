<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('grade_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scale_id')->constrained()->cascadeOnDelete();
            $table->string('label_en');
            $table->string('label_am')->nullable();
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->boolean('triggers_increment')->default(false);
            $table->decimal('increment_pct', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('increment_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_salary', 12, 2);
            $table->decimal('proposed_salary', 12, 2);
            $table->string('status')->default('pending'); // App\Enums\IncrementStatus
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            // FK to payroll_periods added in migration 000012 (table created there)
            $table->unsignedBigInteger('applied_payroll_period_id')->nullable()->index();
            $table->timestamps();
        });

        // Wire the deferred FK from migration 000009.
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('grade_band_id')
                  ->references('id')->on('grade_bands')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['grade_band_id']);
        });
        Schema::dropIfExists('increment_recommendations');
        Schema::dropIfExists('grade_bands');
        Schema::dropIfExists('grade_scales');
    }
};
