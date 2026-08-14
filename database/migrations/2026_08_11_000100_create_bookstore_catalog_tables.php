<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookstore phase 0 — the catalogue of printed course books.
 *
 * Deliberately separate from the ILS `books`/`book_copies` tables: those track
 * individually accessioned lending copies, these track bulk-printed titles held
 * as a quantity. See docs/books-inventory-system.md §1.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Study mode is a lookup table, not an enum, because the seminary adds
        // new modes (Regular, Distance, Evening, Online, …) without a deploy.
        Schema::create('study_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('book_titles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique()->comment('Shelf/label code, e.g. SM-02');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('edition', 60)->nullable();
            $table->string('isbn', 32)->nullable();

            // Course linkage — id for joins, code/name denormalised so a book
            // printed under an old course code still prints correctly.
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('course_code', 60)->nullable();
            $table->string('course_name')->nullable();

            // The three category axes: programme of study, language, study mode.
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('language', 20)->default('am')->comment('App\Enums\Language');
            $table->foreignId('study_mode_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('page_count')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0)->comment('Price charged to the centre/student');
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('Weighted average print cost');
            $table->unsignedInteger('reorder_level')->default(0)->comment('Low-stock threshold');
            $table->unsignedInteger('reorder_quantity')->nullable()->comment('Suggested reprint size');

            $table->string('cover_path')->nullable();
            $table->uuid('tracking_hash')->unique()->comment('QR payload; RFID-compatible later');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['program_id', 'study_mode_id', 'language'], 'book_titles_category_index');
            $table->index('is_active');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_titles');
        Schema::dropIfExists('study_modes');
    }
};
