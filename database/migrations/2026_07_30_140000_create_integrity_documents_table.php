<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrity_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('instructor_id')->constrained('users');
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();

            $table->string('title');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('source'); // App\Enums\IntegrityDocumentSource
            $table->unsignedInteger('word_count')->default(0);
            $table->string('language', 10)->default('en');
            $table->longText('extracted_text')->nullable();
            $table->string('status')->default('pending'); // App\Enums\IntegrityDocumentStatus
            $table->text('failure_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrity_documents');
    }
};
