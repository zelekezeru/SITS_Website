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
        Schema::create('courses', function (Blueprint $table) {
            $table->id(); // Auto-incremented unique ID
            $table->string('title', 50); // Course name
            $table->string('description', 255)->nullable(); // Course description
            $table->string('category', 100); // Course category
            $table->integer('credit_hours'); // Credit hours for the course
            $table->decimal('amount_paid', 10, 2); // Amount paid for the course
            $table->foreignId('program_id') // Foreign key linking to `contact_id`
                  ->nullable()
                  ->constrained('contacts')
                  ->onDelete('set null');
            // $table->boolean('status')->default(true); // Active or inactive (default: active)
            // $table->boolean('visibility')->default(true); // Hidden or visible (default: visible)
            $table->timestamps(); // Created_at and Updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
