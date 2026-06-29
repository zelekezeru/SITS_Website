<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan_name');                  // e.g. "Monthly", "Annual", "Lifetime"
            $table->string('plan_type');                  // monthly | annual | lifetime
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('expiry_date')->nullable();       // null = lifetime
            $table->boolean('is_active')->default(true);
            $table->string('payment_reference')->nullable(); // bank/mobile money ref
            $table->string('payment_method')->nullable();    // CBE, Telebirr, etc.
            $table->unsignedBigInteger('jstore_user_id')->nullable(); // original Joomla JSTORE user ID
            $table->string('jstore_subscription_id')->nullable();     // original JSTORE subscription ID
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_subscriptions');
    }
};
