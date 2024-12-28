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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id(); // Auto-incremented unique ID
            $table->string('title', 50); // Program name
            $table->longText('content'); // Blog content
            $table->string('category', 55); // Blog category
            $table->string('author', 25); // Program division
            $table->date('date'); // Posted date
            $table->integer('views')->default(0); // Number of readers, default is 0
            $table->viewinteger('comments')->default(0); // Number of comments, default is 0
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
        Schema::dropIfExists('blogs');
    }
};
