<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic document vault — DPF credentials, JD scans, task evidence, payslip PDFs, etc.
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // documentable_type, documentable_id
            $table->string('category')->nullable(); // credential|id_copy|certification|jd_scan|evidence
            $table->string('title');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
