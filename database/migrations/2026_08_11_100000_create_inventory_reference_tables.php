<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory reference data: the category tree, suppliers, and the physical
 * location tree.
 *
 * All inventory tables carry the `inventory_` prefix because the Library ILS
 * already owns `categories`, `loans`, `transfers` and `stocktakes` in this same
 * unified database. See docs/inventory-management-design.md §2, §7.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Category tree ───────────────────────────────────────────────────
        // Self-referencing (Furniture → Chairs → Stackable). Carries the
        // defaults new items in the category inherit, so a store keeper adding
        // the 40th laptop doesn't re-enter the depreciation policy.
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->string('code', 12)->unique();  // short prefix used in item SKUs and asset tags (e.g. IT)
            $table->string('name_en');
            $table->string('name_am')->nullable();
            $table->text('description')->nullable();
            $table->string('tracking_mode')->default('consumable');       // App\Enums\InventoryTrackingMode
            $table->string('default_depreciation_method')->default('none'); // App\Enums\DepreciationMethod
            $table->unsignedSmallInteger('default_useful_life_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
        });

        // ── Suppliers ───────────────────────────────────────────────────────
        Schema::create('inventory_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('tin', 30)->nullable();          // Ethiopian taxpayer identification number
            $table->string('contact_person')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account', 60)->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1–5, set from delivery experience
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        // ── Location tree ───────────────────────────────────────────────────
        // Generic and hierarchical rather than a fixed campus/room/shelf triple:
        // a store cares about bins, offices and vehicles at whatever depth the
        // building actually has. Anchored on the shared `campuses` table.
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->string('code', 30)->unique();     // printable/scannable, e.g. LOC-MC-0031
            $table->string('name');
            $table->string('type')->default('room');  // App\Enums\InventoryLocationType
            $table->text('description')->nullable();
            // Who answers for what's here. Nullable: a shelf inherits its store's custodian.
            $table->foreignId('custodian_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_issuable')->default(true); // stock may be issued out of here
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'parent_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_suppliers');
        Schema::dropIfExists('inventory_categories');
    }
};
