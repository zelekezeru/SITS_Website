<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_shelf_box_id')->nullable()->constrained('shelf_boxes')->nullOnDelete();
            $table->foreignId('home_campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->string('tracking_hash')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('accession_number')->nullable()->unique();
            $table->enum('status', [
                'available','checked_out','on_hold','in_transit',
                'pending_transfer','withdrawn','lost','damaged'
            ])->default('available')->index();
            $table->enum('condition', ['new','good','fair','poor'])->default('good');
            $table->date('acquired_on')->nullable();
            $table->decimal('acquisition_cost', 10, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
