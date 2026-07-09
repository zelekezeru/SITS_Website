<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts a closed day from a single `date` into an inclusive `start_date` →
 * `end_date` range, so a holiday/closure can span one day or many.
 *
 * Existing single-date rows are backfilled with start_date = end_date = date.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the range columns (nullable so the backfill can populate them).
        Schema::table('closed_days', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('id');
            $table->date('end_date')->nullable()->after('start_date');
        });

        // 2. Backfill existing single-date rows into the new range columns.
        DB::table('closed_days')->update([
            'start_date' => DB::raw('`date`'),
            'end_date'   => DB::raw('`date`'),
        ]);

        // 3. Drop the old single-date column and its indexes.
        Schema::table('closed_days', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->dropIndex(['is_active', 'date']);
            $table->dropColumn('date');
        });

        // 4. Enforce NOT NULL now that every row has a range, and index it.
        Schema::table('closed_days', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->index(['is_active', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('closed_days', function (Blueprint $table) {
            $table->date('date')->nullable()->after('id');
        });

        // Collapse the range back to its start date.
        DB::table('closed_days')->update([
            'date' => DB::raw('`start_date`'),
        ]);

        Schema::table('closed_days', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'start_date', 'end_date']);
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('closed_days', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->unique('date');
            $table->index(['is_active', 'date']);
        });
    }
};
