<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained()->cascadeOnDelete();
            $table->string('label');                    // "A-01", "A-02"
            $table->string('tracking_hash')->unique();  // UUID — QR now, RFID-ready later
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['row_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_boxes');
    }
};
