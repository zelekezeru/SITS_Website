<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained()->cascadeOnDelete();
            $table->string('name');                 // Q1..Q4
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        Schema::create('fortnights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quarter_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();     // e.g. F12
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        // The canonical sprint unit is the fortnight; days are created lazily.
        Schema::create('days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->foreignId('fortnight_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('days');
        Schema::dropIfExists('fortnights');
        Schema::dropIfExists('quarters');
    }
};
