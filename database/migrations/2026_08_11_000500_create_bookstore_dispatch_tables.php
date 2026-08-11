<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookstore phase 6/7 — dispatch (the waybill) and returns.
 *
 * Together these are the paper "የመጽሃፍ መመዝገቢያ ቅጽ": quantity issued on the left,
 * quantity returned in the middle, quantity outstanding on the right.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number', 30)->unique();
            $table->foreignId('book_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dispatched_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dispatched_at')->useCurrent();
            $table->string('received_by_name')->nullable();
            $table->string('received_by_phone', 30)->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->string('receipt_signature_path')->nullable();
            $table->string('status', 20)->default('prepared')->comment('App\Enums\BookDispatchStatus');
            $table->uuid('tracking_hash')->unique()->comment('QR on the waybill; receiver scans to confirm');
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['book_request_id', 'status']);
        });

        Schema::create('book_dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_dispatch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_section_id')->constrained()->cascadeOnDelete()
                ->comment('Exact section the copies came off');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['book_dispatch_id', 'book_title_id']);
        });

        Schema::create('book_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('book_dispatch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shelf_section_id')->constrained()->cascadeOnDelete()
                ->comment('Section the returned copies are racked back into');
            $table->date('returned_on');
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->string('returned_by_name')->nullable();
            $table->text('condition_note')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->timestamps();

            $table->index(['center_id', 'returned_on']);
        });

        Schema::create('book_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_returned');
            $table->unsignedInteger('quantity_damaged')->default(0)
                ->comment('Comes back but is written off rather than re-shelved');
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->unique(['book_return_id', 'book_title_id'], 'book_return_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_return_items');
        Schema::dropIfExists('book_returns');
        Schema::dropIfExists('book_dispatch_items');
        Schema::dropIfExists('book_dispatches');
    }
};
