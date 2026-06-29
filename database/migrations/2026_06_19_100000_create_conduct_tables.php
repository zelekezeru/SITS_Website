<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conduct_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('issue_type');                       // ConductIssueType enum
            $table->string('severity');                         // ConductSeverity enum
            $table->string('status')->default('draft');         // ConductStatus enum
            $table->text('description_en');
            $table->text('description_am')->nullable();
            $table->text('justification')->nullable();
            $table->dateTime('incident_date');
            $table->string('location')->nullable();
            $table->json('witnesses')->nullable();              // array of witness names/emails

            // Maker-checker actors (audit convention: *_by, no _id suffix; Blameable stamps created_by)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('status');
            $table->index('severity');
            $table->index('issue_type');
        });

        Schema::create('conduct_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conduct_issue_id')->constrained('conduct_issues')->cascadeOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision');                         // ConductDecision enum
            $table->text('decision_notes_en')->nullable();
            $table->text('decision_notes_am')->nullable();
            $table->dateTime('effective_date');
            $table->dateTime('decided_at');
            $table->string('status')->default('active');        // active, appealed, overturned, expired

            $table->foreignId('appealed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('appeal_date')->nullable();
            $table->text('appeal_notes')->nullable();
            $table->foreignId('overturned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('overturned_at')->nullable();
            $table->text('overturn_reason')->nullable();
            $table->dateTime('expires_at')->nullable();         // time-limited decisions (e.g. suspension)

            $table->timestamps();
            $table->softDeletes();

            $table->index('conduct_issue_id');
            $table->index('decision');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduct_decisions');
        Schema::dropIfExists('conduct_issues');
    }
};
