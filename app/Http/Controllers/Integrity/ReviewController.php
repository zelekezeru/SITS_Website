<?php

namespace App\Http\Controllers\Integrity;

use App\Enums\IntegrityReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\IntegrityAuditLog;
use App\Models\IntegrityReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Core review-workflow transition. Phase 7 owns the full acceptance
 * criteria (dedicated tests for the state machine + audit log), but the
 * underlying pieces (IntegrityReviewStatus::canTransitionTo, audit log)
 * already exist from earlier phases, so this is real logic, not a stub.
 */
class ReviewController extends Controller
{
    public function update(Request $request, IntegrityReport $report)
    {
        Gate::authorize('update', $report->document);

        $validated = $request->validate([
            'action' => 'required|in:start,clear,uphold',
            'notes' => 'nullable|string',
            'student_meeting_date' => 'nullable|date',
        ]);

        $target = match ($validated['action']) {
            'start' => IntegrityReviewStatus::UNDER_REVIEW,
            'clear' => IntegrityReviewStatus::CLEARED,
            'uphold' => IntegrityReviewStatus::UPHELD,
        };

        if (! $report->review_status->canTransitionTo($target)) {
            abort(422, "Cannot move a report from '{$report->review_status->value}' to '{$target->value}'.");
        }

        $report->update([
            'review_status' => $target,
            'review_notes' => $validated['notes'] ?? $report->review_notes,
            'student_meeting_date' => $validated['student_meeting_date'] ?? $report->student_meeting_date,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        IntegrityAuditLog::record($report, $request->user(), $target->value, [
            'notes' => $validated['notes'] ?? null,
            'student_meeting_date' => $validated['student_meeting_date'] ?? null,
        ]);

        return back()->with('success', 'Review updated.');
    }
}
