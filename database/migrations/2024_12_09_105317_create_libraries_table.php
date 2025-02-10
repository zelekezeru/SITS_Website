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
        Schema::create('libraries', function (Blueprint $table) {
            $table->id(); // Auto-incremented unique ID
            $table->string('title', 50); // Customer name
            $table->longText('description')->nullable()->unique(); // Email address
            $table->string('banner')->nullable(); // Phone number
            $table->string('link')->nullable(); // Message title
            $table->string('category')->nullable(); // Message category
            $table->string('file')->nullable(); // Message file
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
        Schema::dropIfExists('libraries');
    }
};
