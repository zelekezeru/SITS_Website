<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('staff_no')->unique();                 // e.g. SITS-2026-042
            $table->string('full_name_en');
            $table->string('full_name_am')->nullable();           // native name, e.g. እልፍነሽ ደደፋ
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporting_to_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employment_type')->default('full_time'); // App\Enums\EmploymentType
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->unsignedSmallInteger('legal_daily_hour_limit')->default(8); // Ethiopian labour-law base
            $table->date('hired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
