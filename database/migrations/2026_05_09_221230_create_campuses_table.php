<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SITS Library merge — `campuses` reconciliation.
 *
 * The ERP already ships a `campuses` table (id, name_en, name_am, city, is_active).
 * The library needs a single shared campus concept, so instead of creating a second
 * table we ADD the library's columns (name, code, address, soft-deletes) to the
 * existing one and backfill `name` from `name_en`. On a fresh DB (no ERP), we create
 * the library's own table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campuses')) {
            Schema::table('campuses', function (Blueprint $table) {
                if (! Schema::hasColumn('campuses', 'name'))       $table->string('name')->nullable()->after('id');
                if (! Schema::hasColumn('campuses', 'code'))       $table->string('code')->nullable()->unique();
                if (! Schema::hasColumn('campuses', 'address'))    $table->string('address')->nullable();
                if (! Schema::hasColumn('campuses', 'deleted_at')) $table->softDeletes();
            });

            // Backfill the library `name` from the ERP bilingual name so existing
            // campuses are visible to the library UI.
            if (Schema::hasColumn('campuses', 'name_en')) {
                DB::statement("UPDATE campuses SET name = name_en WHERE (name IS NULL OR name = '')");
            }
        } else {
            Schema::create('campuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // If this was the shared ERP table (has name_en), only drop the columns we added.
        if (Schema::hasTable('campuses') && Schema::hasColumn('campuses', 'name_en')) {
            Schema::table('campuses', function (Blueprint $table) {
                foreach (['name', 'code', 'address', 'deleted_at'] as $col) {
                    if (Schema::hasColumn('campuses', $col)) $table->dropColumn($col);
                }
            });
        } else {
            Schema::dropIfExists('campuses');
        }
    }
};
