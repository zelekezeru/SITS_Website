<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrity_document_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('ai_probability')->nullable(); // 0-100
            $table->string('confidence')->nullable();     // App\Enums\IntegrityConfidence
            $table->string('verdict_label', 40)->nullable(); // App\Enums\IntegrityVerdict
            $table->json('statistical_signals')->nullable();
            $table->json('claude_analysis')->nullable();
            $table->json('sentence_scores')->nullable();
            $table->boolean('flagged')->default(false);

            $table->string('review_status')->default('none'); // App\Enums\IntegrityReviewStatus
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->string('engine_version', 20)->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrity_reports');
    }
};
