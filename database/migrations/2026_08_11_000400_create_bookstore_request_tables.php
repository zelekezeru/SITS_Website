<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookstore phase 4/5 — the request workflow and its money.
 *
 * `book_requests` + `book_request_items` + `book_request_approvals` are the
 * paper "የመጽሃፍት መጠየቂያ ቅጽ": header, lines, and the four signature blocks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('coordinator_name')->nullable();
            $table->string('coordinator_phone', 30)->nullable();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('student_count')->default(0);
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Parent campus the centre reports to, when applicable');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        Schema::create('book_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->unique();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('destination_type', 20)->comment('App\Enums\RequestDestination');
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('student_count')->default(0)
                ->comment('Verified students the request is sized against');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();

            $table->string('status', 30)->default('draft')->comment('App\Enums\BookRequestStatus');
            $table->date('needed_by')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // One actor/timestamp pair per workflow stage.
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['center_id', 'status']);
            $table->index(['campus_id', 'status']);
        });

        Schema::create('book_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_approved')->default(0);
            $table->unsignedInteger('quantity_dispatched')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->unique(['book_request_id', 'book_title_id'], 'book_request_items_unique');
        });

        Schema::create('book_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_request_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 30)->comment('App\Enums\BookRequestStage');
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 20)->comment('App\Enums\ApprovalDecision');
            $table->text('note')->nullable();
            $table->timestamp('acted_at')->useCurrent();
            $table->timestamps();

            $table->index(['book_request_id', 'stage']);
        });

        Schema::create('book_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_request_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30)->comment('App\Enums\BookPaymentMethod');
            $table->string('bank_name')->nullable();
            $table->string('transaction_reference', 80)->nullable()->comment('Bank/wallet transaction number');
            $table->string('crv_number', 60)->nullable()->comment('Manual cash receipt voucher number');
            $table->string('receipt_number', 60)->nullable();
            $table->date('paid_on');
            $table->string('receipt_image_path')->nullable()->comment('Private disk; streamed, never public');
            $table->string('status', 20)->default('pending')->comment('App\Enums\BookPaymentStatus');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['book_request_id', 'status']);
            $table->index('transaction_reference');
            $table->index('crv_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_payments');
        Schema::dropIfExists('book_request_approvals');
        Schema::dropIfExists('book_request_items');
        Schema::dropIfExists('book_requests');
        Schema::dropIfExists('centers');
    }
};
