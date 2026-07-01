<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('in_progress')->comment('in_progress, completed, cancelled');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['campus_id', 'status']);
        });

        Schema::create('stocktake_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_copy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scanned_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('location_match')->default(true)->comment('Whether the copy was found at its expected shelf');
            $table->string('found_location')->nullable()->comment('Actual location if different from expected');
            $table->text('note')->nullable();
            $table->timestamp('scanned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['stocktake_id', 'book_copy_id']); // Don't scan same copy twice
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocktake_scans');
        Schema::dropIfExists('stocktakes');
    }
};
