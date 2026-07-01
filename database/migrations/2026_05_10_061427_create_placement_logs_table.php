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
        Schema::create('placement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_shelf_box_id')->nullable()->constrained('shelf_boxes')->nullOnDelete();
            $table->foreignId('to_shelf_box_id')->nullable()->constrained('shelf_boxes')->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('reason', ['initial_place','reshelve','transfer','correction'])->default('reshelve');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placement_logs');
    }
};
