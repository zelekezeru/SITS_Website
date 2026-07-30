<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corpus_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrity_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('shingle_hash');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index('shingle_hash');
            $table->index(['shingle_hash', 'integrity_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corpus_fingerprints');
    }
};
