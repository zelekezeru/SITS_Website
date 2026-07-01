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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained();
            $table->foreignId('from_campus_id')->constrained('campuses');
            $table->foreignId('to_campus_id')->constrained('campuses');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('dispatched_by')->nullable()->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->foreignId('reason_hold_id')->nullable()->constrained('holds')->nullOnDelete();
            $table->foreignId('reason_loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->enum('status', [
                'requested','approved','rejected','in_transit',
                'received','cancelled','returned_to_origin','lost'
            ])->default('requested')->index();
            $table->text('reason')->nullable();
            $table->text('rejection_note')->nullable();
            $table->string('courier_ref')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
