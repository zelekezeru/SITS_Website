<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // KPIs are standalone (isolated from tasks). Ownership is polymorphic:
        // kpiable = JobDescriptionVersion (role-based) OR Target (strategic). Never a Task.
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('kpiable'); // kpiable_type, kpiable_id
            $table->string('title_en');
            $table->string('title_am')->nullable();
            $table->string('measure_type')->default('quantitative'); // App\Enums\MeasureType
            $table->decimal('target_value', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->boolean('is_dynamic')->default(false);
            $table->string('status')->default('created');            // App\Enums\KpiStatus
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();   // maker
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();  // checker
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_kpi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'kpi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_kpi');
        Schema::dropIfExists('kpis');
    }
};
