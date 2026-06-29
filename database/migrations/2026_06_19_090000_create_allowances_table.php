<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recurring monthly allowances paid on top of base salary. Taxability
        // follows Ethiopian rules: transport is exempt up to a cap, others are
        // taxable unless explicitly flagged otherwise.
        Schema::create('allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('other'); // App\Enums\AllowanceType
            $table->string('label')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowances');
    }
};
