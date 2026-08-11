<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The catalog and the receiving record.
 *
 * `inventory_items` is the *definition* of a thing; `inventory_batches` is one
 * buying event. Purchase date, supplier, quantity purchased, unit cost, who
 * received it and who registered it all live on the batch — an item bought three
 * times has three of each, so putting them on the item would be lossy.
 * See docs/inventory-management-design.md §1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();  // SKU, e.g. IT-00184
            $table->foreignId('category_id')->constrained('inventory_categories')->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_am')->nullable();
            $table->text('description')->nullable();

            // The branch the whole schema turns on — App\Enums\InventoryTrackingMode.
            $table->string('tracking_mode')->default('consumable');
            $table->string('unit_of_measure')->default('piece'); // App\Enums\UnitOfMeasure
            $table->string('status')->default('active');         // App\Enums\InventoryItemStatus

            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('specification')->nullable(); // free text: size, colour, rating, capacity

            // Reorder policy — drives the store dashboard's primary alert.
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->decimal('reorder_quantity', 14, 3)->nullable();
            $table->decimal('standard_unit_cost', 14, 2)->nullable(); // planning figure; actuals come from batches

            // Consumables with a shelf life get expiry tracking on their batches.
            $table->boolean('tracks_expiry')->default(false);
            // Assets whose defaults differ from their category's.
            $table->string('depreciation_method')->nullable();       // App\Enums\DepreciationMethod
            $table->unsignedSmallInteger('useful_life_months')->nullable();

            // Item photos and documents hang off the polymorphic `documents`
            // table; this pins which one is the list thumbnail. No FK constraint:
            // documents rows are deleted freely and a dangling id is harmless.
            $table->unsignedBigInteger('primary_image_id')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index('tracking_mode');
            $table->index('name_en');
        });

        // ── Goods received note (GRN) ───────────────────────────────────────
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 30)->unique(); // e.g. GRN-2026-0043
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('inventory_suppliers')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();

            $table->decimal('quantity_received', 14, 3);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->string('currency', 3)->default('ETB');
            $table->decimal('total_cost', 16, 2)->nullable(); // stored, not derived: invoices carry rounding and fees

            $table->date('purchase_date');
            $table->date('production_date')->nullable(); // optional, per the requirement
            $table->date('expiry_date')->nullable();
            $table->date('warranty_until')->nullable();

            $table->string('invoice_number')->nullable();
            $table->string('purchase_order_number')->nullable(); // link point for a future procurement module
            $table->string('delivery_note_number')->nullable();

            $table->string('condition_on_arrival')->default('new'); // App\Enums\InventoryCondition

            // Two distinct people, deliberately: the storekeeper who physically
            // took delivery, and the user account that entered the record.
            $table->foreignId('received_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_id', 'purchase_date']);
            $table->index('expiry_date');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_items');
    }
};
