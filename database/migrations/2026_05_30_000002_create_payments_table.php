<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();          // who the fine belongs to
            $table->foreignId('recorded_by')->nullable()->constrained('users'); // staff, for manual
            $table->decimal('amount', 8, 2);
            $table->string('currency', 8)->default('ETB');
            $table->string('method')->default('cash');            // cash, chapa, telebirr, stripe
            $table->string('status')->default('pending');         // pending, success, failed
            $table->string('reference')->unique();                // tx_ref
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
