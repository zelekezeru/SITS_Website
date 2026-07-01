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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('checked_out_by')->constrained('users');
            $table->foreignId('checked_out_at_campus_id')->constrained('campuses');
            $table->timestamp('checked_out_at');
            $table->date('due_on');
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_to_campus_id')->nullable()->constrained('campuses');
            $table->foreignId('returned_by')->nullable()->constrained('users');
            $table->unsignedTinyInteger('renewal_count')->default(0);
            $table->enum('status', ['active','returned','overdue_returned','lost'])->default('active');
            $table->timestamps();
            $table->index(['user_id','status']);
            $table->index(['book_copy_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
