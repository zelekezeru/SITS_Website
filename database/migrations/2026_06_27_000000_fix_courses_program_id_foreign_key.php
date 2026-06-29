<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix: courses.program_id incorrectly referenced the `contacts` table.
     * This migration drops the bad FK constraint and adds the correct one
     * pointing to the `programs` table.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop the incorrect foreign key that pointed to `contacts`
            $table->dropForeign(['program_id']);

            // Add the correct foreign key pointing to `programs`
            $table->foreign('program_id')
                  ->references('id')
                  ->on('programs')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migration — restore the old (incorrect) constraint.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['program_id']);

            $table->foreign('program_id')
                  ->references('id')
                  ->on('contacts')
                  ->onDelete('set null');
        });
    }
};
