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
        Schema::create('secure_document_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secure_document_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('grantee');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['secure_document_id', 'grantee_type', 'grantee_id'], 'sda_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secure_document_accesses');
    }
};
