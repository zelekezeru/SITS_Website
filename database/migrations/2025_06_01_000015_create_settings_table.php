<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generalized, audited key/value store (replaces the payroll-only settings table).
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general'); // payroll|scoring|general
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');   // string|decimal|integer|boolean|json
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
