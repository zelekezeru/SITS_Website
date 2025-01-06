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
        Schema::create('trainers', function (Blueprint $table) {
            $table->id(); // Auto-incremented unique ID
            $table->string('name', 50); // Trainer name
            $table->string('position', 50); // Trainer name
            $table->string('description', 255)->nullable(); // Trainer description
            $table->string('image');
            $table->timestamps(); // Created_at and Updated_at timestamps   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
