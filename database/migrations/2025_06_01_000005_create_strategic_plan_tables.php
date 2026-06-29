<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('years', function (Blueprint $table) {
            $table->id();
            $table->string('label');                 // e.g. FY2026
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(false); // activating one deactivates the rest (app logic)
            $table->timestamps();
        });

        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained()->cascadeOnDelete();
            $table->string('pillar');               // App\Enums\StrategicPillar
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('value', 12, 2)->default(0);   // numeric measurable output
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('department_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['department_id', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_target');
        Schema::dropIfExists('targets');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('strategies');
        Schema::dropIfExists('years');
    }
};
