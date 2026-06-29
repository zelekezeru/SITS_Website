<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            // Maker-checker: Finance prepares & submits, President approves.
            $table->foreignId('prepared_by')->nullable()->after('payment_date')->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable()->after('prepared_by');
            $table->timestamp('submitted_at')->nullable()->after('prepared_at');
            $table->foreignId('approved_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('review_notes')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prepared_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['prepared_at', 'submitted_at', 'approved_at', 'review_notes']);
        });
    }
};
