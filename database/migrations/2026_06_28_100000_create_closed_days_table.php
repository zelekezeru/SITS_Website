<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closed Days / Holiday Calendar
 *
 * Stores declared non-working days (public holidays, special closures, official
 * institute closures) that can be attached to a Mass Permission to automatically
 * excuse absences for all active employees during that period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closed_days', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');                          // e.g. "Ethiopian Christmas"
            $table->string('type')->default('holiday');      // App\Enums\ClosedDayType
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('date');
            $table->index(['is_active', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closed_days');
    }
};
