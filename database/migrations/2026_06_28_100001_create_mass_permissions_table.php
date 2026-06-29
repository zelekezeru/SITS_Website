<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mass Permission requests and their closed-day attachments.
 *
 * A Mass Permission is a batch excused-absence request that covers one or more
 * declared closed days for ALL active employees in a given payroll period.
 *
 * Approval workflow:
 *   draft → pending_approval → pending_confirmation → approved / rejected
 *
 * On final approval the system spawns one AttendancePermission row per active
 * employee (status = approved, days = total_days) so that the PayrollRunService
 * picks them up without any changes to the calculation engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Main mass permissions table ────────────────────────────────────────
        Schema::create('mass_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                          // e.g. "Ethiopian Christmas 2025"
            $table->text('reason')->nullable();                              // admin justification
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');                      // MassPermissionStatus
            $table->unsignedSmallInteger('total_days')->default(0);         // # closed days attached
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            // First approval layer
            $table->foreignId('first_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_approved_at')->nullable();
            $table->text('first_review_notes')->nullable();

            // Final confirmation layer
            $table->foreignId('final_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('final_approved_at')->nullable();
            $table->text('final_review_notes')->nullable();

            // Post-approval tracking
            $table->unsignedInteger('employees_affected')->nullable();
            $table->boolean('permissions_spawned')->default(false);

            $table->timestamps();

            $table->index(['payroll_period_id', 'status']);
        });

        // ── Pivot: mass_permission ↔ closed_day ────────────────────────────────
        Schema::create('closed_day_mass_permission', function (Blueprint $table) {
            $table->foreignId('mass_permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('closed_day_id')->constrained()->cascadeOnDelete();
            $table->primary(['mass_permission_id', 'closed_day_id']);
        });

        // ── Link spawned AttendancePermissions back to their mass permission ───
        Schema::table('attendance_permissions', function (Blueprint $table) {
            $table->foreignId('mass_permission_id')
                ->nullable()
                ->after('review_notes')
                ->constrained('mass_permissions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_permissions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\MassPermission::class);
            $table->dropColumn('mass_permission_id');
        });

        Schema::dropIfExists('closed_day_mass_permission');
        Schema::dropIfExists('mass_permissions');
    }
};
