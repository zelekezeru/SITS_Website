<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookstore phase 2/3 — the stock engine.
 *
 * `book_stocks` is a per-location balance cache. `stock_movements` is the truth:
 * an append-only bin card, column-for-column the paper "SBCE STORE LOG".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_section_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0)
                ->comment('Held for verified requests; deducted from availability, not from quantity');
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['book_title_id', 'shelf_section_id'], 'book_stocks_title_section_unique');
            $table->index('shelf_section_id');
        });

        Schema::create('print_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number', 40)->unique();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->string('printer_name')->nullable();
            $table->string('invoice_number', 60)->nullable();
            $table->string('crv_number', 60)->nullable()->comment('Manual cash receipt voucher number');
            $table->date('printed_on')->nullable();
            $table->date('received_on');
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shelf_section_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['book_title_id', 'received_on']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->comment('App\Enums\StockMovementType');
            $table->unsignedInteger('quantity')->comment('Always positive; direction comes from type');
            $table->integer('balance_after')->comment('Running balance at this section after the movement');
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 14, 2)->nullable();
            $table->nullableMorphs('reference');
            $table->string('reference_number', 60)->nullable()->comment('CRV / invoice / waybill number');
            $table->string('description')->nullable();
            $table->string('remark')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('occurred_at')->useCurrent()->comment('May be back-dated to match the paper log');
            $table->timestamps();

            $table->index(['book_title_id', 'occurred_at'], 'stock_movements_title_date_index');
            $table->index(['shelf_section_id', 'occurred_at'], 'stock_movements_section_date_index');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('print_runs');
        Schema::dropIfExists('book_stocks');
    }
};
