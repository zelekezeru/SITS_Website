<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookstore phase 1 — the warehouse location tree.
 *
 *   StoreRoom → Shelf → ShelfSection
 *
 * Every level carries a tracking_hash so every level gets a printable QR label.
 * Only ShelfSection holds stock; the parents aggregate. This is intentionally a
 * different tree from the ILS Floor → Row → ShelfBox reading-room hierarchy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('location_note')->nullable()->comment('Building / floor description');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('tracking_hash')->unique();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'is_active']);
        });

        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_room_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('label')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->uuid('tracking_hash')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_room_id', 'code']);
        });

        Schema::create('shelf_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->comment('e.g. SM-02, matching the sticky label');
            $table->string('name')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->uuid('tracking_hash')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shelf_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_sections');
        Schema::dropIfExists('shelves');
        Schema::dropIfExists('store_rooms');
    }
};
