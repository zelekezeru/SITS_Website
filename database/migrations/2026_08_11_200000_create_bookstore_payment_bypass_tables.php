<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment deferral ("pay later") and stage-lag measurement.
 *
 * Finance may ask for the payment gate to be released before the money is in.
 * That is a two-person decision — a reason from Finance, a justification from an
 * authoriser — and the debt stays visible until it is settled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_payment_bypasses', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('book_request_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 14, 2)->comment('Amount being deferred at the time of the request');
            $table->date('promised_on')->nullable()->comment('When Finance expects the money');
            $table->text('reason')->comment('Why Finance is asking to defer — required');

            $table->string('status', 20)->default('pending')->comment('App\Enums\PaymentBypassStatus');

            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at')->useCurrent();

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('justification')->nullable()->comment('The authoriser\'s reasoning — required to approve');
            $table->text('rejection_reason')->nullable();

            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['book_request_id', 'status']);
            $table->index('status');
        });

        // How long each stage actually waited, frozen at the moment it was acted
        // on. Derivable from timestamps, but storing it makes "where is the lag"
        // a plain average instead of a window function over the whole trail.
        Schema::table('book_request_approvals', function (Blueprint $table) {
            $table->unsignedInteger('waited_seconds')->nullable()->after('acted_at')
                ->comment('Seconds this stage sat waiting before the actor acted');
        });
    }

    public function down(): void
    {
        Schema::table('book_request_approvals', function (Blueprint $table) {
            $table->dropColumn('waited_seconds');
        });

        Schema::dropIfExists('book_payment_bypasses');
    }
};
