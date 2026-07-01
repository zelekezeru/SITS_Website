<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            $table->string('label');                    // "A", "B", "Row 3"
            $table->string('subject_area')->nullable(); // optional: Fiction, CS, Law
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['floor_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rows');
    }
};
