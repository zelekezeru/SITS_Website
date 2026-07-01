<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('secure_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('disk')->default('archive');
            $table->string('path');
            $table->string('original_filename');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime')->default('application/pdf');
            $table->string('sha256', 64);
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->enum('visibility', ['role_gated', 'document_gated', 'public_authenticated'])->default('role_gated');
            $table->boolean('watermark_user')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['book_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secure_documents');
    }
};
