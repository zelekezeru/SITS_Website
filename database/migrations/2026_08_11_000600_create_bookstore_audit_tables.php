<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookstore phase 8 — physical stock counting and verification.
 *
 * The counter walks the aisle scanning each section's QR; corrections reach the
 * ledger only when an approver signs off the variance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_audits', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('store_room_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft')->comment('App\Enums\StockAuditStatus');
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_room_id', 'status']);
        });

        Schema::create('stock_audit_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('system_quantity')->comment('Frozen when the audit started');
            $table->unsignedInteger('counted_quantity')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['stock_audit_id', 'shelf_section_id', 'book_title_id'], 'stock_audit_lines_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_audit_lines');
        Schema::dropIfExists('stock_audits');
    }
};
