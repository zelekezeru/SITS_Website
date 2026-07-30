<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plagiarism_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrity_document_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('overall_similarity')->default(0); // 0-100
            $table->json('matches')->nullable();
            $table->unsignedInteger('corpus_size')->default(0);
            $table->timestamp('analyzed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plagiarism_reports');
    }
};
