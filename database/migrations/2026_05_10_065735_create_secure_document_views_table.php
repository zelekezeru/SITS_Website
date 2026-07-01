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
        Schema::create('secure_document_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secure_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('ip', 45);
            $table->string('user_agent', 500);
            $table->unsignedSmallInteger('pages_viewed')->default(0);
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secure_document_views');
    }
};
