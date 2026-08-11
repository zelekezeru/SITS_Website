<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ledger, the requisition flow and the stocktake.
 *
 * `inventory_stock_movements` is the source of truth for every quantity in the
 * module: on_hand(item, location) = Σ quantity. It is append-only — a mistake is
 * corrected by a compensating row with a stated reason, so the audit trail shows
 * both the error and the correction. See docs/inventory-management-design.md §1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->unique(); // e.g. REQ-2026-0912
            $table->foreignId('requested_by_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft'); // App\Enums\InventoryRequestStatus

            $table->text('purpose')->nullable();
            $table->date('needed_by')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index('requested_by_employee_id');
            $table->index('department_id');
        });

        Schema::create('inventory_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('inventory_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            // Set when the requester (or approver) names a specific asset.
            $table->foreignId('unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();

            // Invariant 5: issued ≤ approved ≤ requested. Three columns, not one,
            // so partial fulfilment is a first-class state rather than a comment.
            $table->decimal('quantity_requested', 14, 3);
            $table->decimal('quantity_approved', 14, 3)->nullable();
            $table->decimal('quantity_issued', 14, 3)->default(0);

            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['request_id', 'item_id']);
        });

        // ── The ledger ──────────────────────────────────────────────────────
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();

            $table->string('type');                       // App\Enums\InventoryMovementType
            // Signed: positive adds, negative removes. The enum's direction() is
            // the authority on the sign; nothing else decides it.
            $table->decimal('quantity', 14, 3);

            $table->foreignId('from_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();

            // The counterparty: who received an issue, or handed back a return.
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('inventory_requests')->nullOnDelete();
            $table->foreignId('disposal_id')->nullable()->constrained('inventory_disposals')->nullOnDelete();

            $table->string('reference', 40)->nullable();  // voucher number, e.g. ISV-2026-0455
            $table->decimal('unit_cost', 14, 2)->nullable(); // snapshot for FIFO valuation

            $table->timestamp('occurred_at');             // when it physically happened
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();         // mandatory for adjustments and write-offs
            $table->text('notes')->nullable();
            $table->timestamps();

            // on_hand sums by item and by location, so both need to be cheap.
            $table->index(['item_id', 'occurred_at']);
            $table->index(['to_location_id', 'item_id']);
            $table->index(['from_location_id', 'item_id']);
            $table->index(['unit_id', 'occurred_at']);
            $table->index('type');
            $table->index('reference');
        });

        Schema::create('inventory_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique(); // e.g. STK-2026-007
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->string('status')->default('open'); // App\Enums\InventoryStocktakeStatus
            $table->string('scope')->nullable();       // human description of what was counted

            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            // Posting turns variances into adjustment movements and needs the
            // adjust permission — never the same person's job as counting.
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('inventory_stocktake_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocktake_id')->constrained('inventory_stocktakes')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();

            // Snapshotted when the session opens, so a movement posted mid-count
            // can't silently change what the variance is measured against.
            $table->decimal('system_quantity', 14, 3);
            $table->decimal('counted_quantity', 14, 3)->nullable();
            $table->decimal('variance', 14, 3)->nullable(); // counted − system, stored for reporting
            $table->string('variance_reason')->nullable();

            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->index(['stocktake_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocktake_lines');
        Schema::dropIfExists('inventory_stocktakes');
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_request_lines');
        Schema::dropIfExists('inventory_requests');
    }
};
