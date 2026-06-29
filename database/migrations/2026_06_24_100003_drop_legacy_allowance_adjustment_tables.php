<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The unified payroll_components + payroll_component_assignments model supersedes
 * the standalone allowances table and the per-period payroll_adjustments table.
 * down() recreates them minimally so the migration is reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('allowances');
    }

    public function down(): void
    {
        Schema::create('allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
