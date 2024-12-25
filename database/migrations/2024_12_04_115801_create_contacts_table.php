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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id(); // Auto-incremented unique ID
            $table->string('name', 50); // Customer name
            $table->string('email')->unique(); // Email address
            $table->string('phone', 15); // Phone number
            $table->string('title', 100); // Message title
            $table->longText('message'); // Main message
            $table->foreignId('reply') // Foreign key linking to `contact_id`
                  ->nullable()
                  ->constrained('contacts')
                  ->onDelete('set null');
            $table->boolean('status')->default(true); // Active or inactive (default: active)
            $table->boolean('visibility')->default(true); // Hidden or visible (default: visible)
            $table->timestamps(); // Created_at and Updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
