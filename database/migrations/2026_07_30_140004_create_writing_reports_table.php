<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrity_document_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // App\Enums\WritingReportType
            $table->json('payload')->nullable();
            $table->string('model', 40)->nullable();
            $table->json('token_usage')->nullable();
            $table->timestamps();

            $table->index(['integrity_document_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_reports');
    }
};
