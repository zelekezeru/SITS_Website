<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixed assets: one row per physical thing, plus its custody, maintenance and
 * disposal history.
 *
 * `assigned_to_employee_id` on the unit is a denormalized pointer to the open
 * assignment — convenient for list queries. The *history* lives in
 * inventory_asset_assignments, which is what answers "who has had this, and what
 * condition did each of them return it in".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();

            $table->string('asset_tag', 40)->unique();       // QR-printable, e.g. SITS-IT-000871
            $table->string('serial_number')->nullable();
            $table->string('status')->default('in_store');   // App\Enums\InventoryUnitStatus
            $table->string('condition')->default('new');     // App\Enums\InventoryCondition

            $table->foreignId('current_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('assigned_to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->string('depreciation_method')->default('none'); // App\Enums\DepreciationMethod
            $table->unsignedSmallInteger('useful_life_months')->nullable();
            $table->decimal('salvage_value', 14, 2)->default(0);
            $table->date('in_service_on')->nullable(); // depreciation starts here, not at purchase
            $table->date('warranty_until')->nullable();

            $table->timestamp('last_maintenance_at')->nullable();
            $table->date('next_maintenance_due_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_id', 'status']);
            $table->index('assigned_to_employee_id');
            $table->index('current_location_id');
            $table->index('next_maintenance_due_at');
            $table->index('serial_number');
        });

        // ── Custody ledger ──────────────────────────────────────────────────
        Schema::create('inventory_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('inventory_units')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->timestamp('issued_at');
            $table->date('due_at')->nullable();      // null = indefinite (a desk), a date = a loan
            $table->timestamp('returned_at')->nullable();

            $table->string('condition_out')->default('good'); // App\Enums\InventoryCondition
            $table->string('condition_in')->nullable();       // set on return; worse than out = attributable damage

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_back_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('purpose')->nullable();
            $table->string('acknowledgement_path')->nullable(); // signed handover slip, private disk
            $table->text('notes')->nullable();
            $table->timestamps();

            // Invariant 3: at most one open assignment per unit. Enforced in the
            // service layer — a partial unique index isn't portable across the
            // MySQL/SQLite pair this project runs on.
            $table->index(['unit_id', 'returned_at']);
            $table->index(['employee_id', 'returned_at']);
            $table->index('due_at');
        });

        Schema::create('inventory_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('inventory_units')->cascadeOnDelete();
            $table->string('type')->default('repair'); // App\Enums\InventoryMaintenanceType

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('inventory_suppliers')->nullOnDelete();
            $table->string('vendor_name')->nullable(); // for one-off vendors not worth a supplier record

            $table->decimal('cost', 14, 2)->nullable();
            $table->string('currency', 3)->default('ETB');
            $table->date('started_at');
            $table->date('completed_at')->nullable();
            $table->date('next_due_at')->nullable();
            $table->unsignedSmallInteger('downtime_days')->nullable();

            $table->text('fault_description')->nullable();
            $table->text('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'started_at']);
            $table->index('next_due_at');
        });

        Schema::create('inventory_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique(); // e.g. DSP-2026-0012

            // A disposal covers either one serialized unit, or a quantity of a
            // consumable item (an expired lot). Exactly one of the two is set.
            $table->foreignId('unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->decimal('quantity', 14, 3)->nullable();

            $table->string('method')->default('written_off');  // App\Enums\InventoryDisposalMethod
            $table->string('status')->default('proposed');      // App\Enums\InventoryDisposalStatus
            $table->text('reason');

            $table->decimal('book_value', 14, 2)->nullable();
            $table->decimal('proceeds', 14, 2)->nullable();     // required when method yields proceeds
            $table->string('recipient')->nullable();            // buyer or donee

            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('proposed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_disposals');
        Schema::dropIfExists('inventory_maintenance_logs');
        Schema::dropIfExists('inventory_asset_assignments');
        Schema::dropIfExists('inventory_units');
    }
};
