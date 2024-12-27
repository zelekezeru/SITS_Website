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
        Schema::create('events', function (Blueprint $table) {
            $table->id(); // Auto-incremented unique ID
            $table->string('title', 50); // Event title
            $table->string('description', 255)->nullable(); // Event description
            $table->date('date'); // Event date
            $table->time('start_time'); // Starting time
            $table->time('end_time'); // Ending time
            $table->string('location', 50); // Event held place
            $table->string('remark', 50)->nullable(); // Remarks or reviews
            $table->boolean('status')->default(true); // Active or inactive
            $table->boolean('visibility')->default(true); // Hidden or visible
            $table->timestamps(); // Created_at and Updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
