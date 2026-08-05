<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns medical allowance enrollment into a request → admin-approval flow,
 * mirroring how AttendancePermission works. `medical_allowance_enabled`
 * (added earlier) stays the *effective* flag MedicalAllowanceClaimController
 * gates eligibility on — it only flips true once an admin approves the
 * request recorded here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('medical_allowance_requested')->default(false)->after('medical_allowance_enabled');
            $table->foreignId('medical_allowance_requested_by')->nullable()->after('medical_allowance_requested')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('medical_allowance_requested_at')->nullable()->after('medical_allowance_requested_by');
            $table->foreignId('medical_allowance_reviewed_by')->nullable()->after('medical_allowance_requested_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('medical_allowance_reviewed_at')->nullable()->after('medical_allowance_reviewed_by');
            $table->text('medical_allowance_rejection_reason')->nullable()->after('medical_allowance_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medical_allowance_requested_by');
            $table->dropConstrainedForeignId('medical_allowance_reviewed_by');
            $table->dropColumn([
                'medical_allowance_requested',
                'medical_allowance_requested_at',
                'medical_allowance_reviewed_at',
                'medical_allowance_rejection_reason',
            ]);
        });
    }
};
