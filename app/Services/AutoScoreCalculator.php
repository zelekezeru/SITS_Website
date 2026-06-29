<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Deliverable;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationRating;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Support\Carbon;

/**
 * AutoScoreCalculator
 *
 * Derives the `auto_score` for an Evaluation from four real data components:
 *
 *   1. Task completion       (default 40%)
 *   2. Deliverable completion (default 25%)
 *   3. KPI rating achievement (default 25%)
 *   4. Attendance             (default 10%)
 *
 * Each sub-score is 0–100. Weights are configurable via the Settings table.
 * If a component has no data (e.g., zero tasks), its weight is redistributed
 * proportionally across the remaining components.
 */
class AutoScoreCalculator
{
    /**
     * Compute auto_score for an evaluation and return a detailed breakdown.
     *
     * @return array{auto_score: float, breakdown: array}
     */
    public function compute(Evaluation $evaluation): array
    {
        $evaluation->loadMissing(['employee', 'period']);

        $employee = $evaluation->employee;
        $period   = $evaluation->period;

        if (!$employee || !$period) {
            return [
                'auto_score' => 0,
                'breakdown'  => [],
                'error'      => 'Missing employee or evaluation period.',
            ];
        }

        // ── Load configurable weights ────────────────────────────
        $weights = [
            'tasks'        => (float) Setting::get('auto_score_weight_tasks', 0.40),
            'deliverables' => (float) Setting::get('auto_score_weight_deliverables', 0.25),
            'kpis'         => (float) Setting::get('auto_score_weight_kpis', 0.25),
            'attendance'   => (float) Setting::get('auto_score_weight_attendance', 0.10),
        ];

        $overduePenalty = (float) Setting::get('auto_score_overdue_penalty', 10.0);

        // ── Calculate each component ─────────────────────────────
        $components = [
            'tasks'        => $this->computeTaskScore($employee, $period, $overduePenalty),
            'deliverables' => $this->computeDeliverableScore($employee, $period),
            'kpis'         => $this->computeKpiScore($evaluation, $employee),
            'attendance'   => $this->computeAttendanceScore($employee, $period),
        ];

        // ── Redistribute weights for empty components ────────────
        $activeWeights  = [];
        $inactiveWeight = 0;

        foreach ($components as $key => $data) {
            if ($data['has_data']) {
                $activeWeights[$key] = $weights[$key];
            } else {
                $inactiveWeight += $weights[$key];
            }
        }

        // If nothing has data, everything is 0
        if (empty($activeWeights)) {
            return [
                'auto_score' => 0,
                'breakdown'  => $this->buildBreakdown($components, $weights),
            ];
        }

        // Redistribute inactive weight proportionally
        $totalActive = array_sum($activeWeights);
        if ($inactiveWeight > 0 && $totalActive > 0) {
            foreach ($activeWeights as $key => &$w) {
                $w += ($w / $totalActive) * $inactiveWeight;
            }
            unset($w);
        }

        // ── Compute final auto_score ─────────────────────────────
        $autoScore = 0;
        foreach ($activeWeights as $key => $w) {
            $autoScore += $components[$key]['score'] * $w;
        }

        $autoScore = round(min(100, max(0, $autoScore)), 2);

        // Merge effective weights into breakdown
        $breakdown = $this->buildBreakdown($components, $weights, $activeWeights);

        return [
            'auto_score' => $autoScore,
            'breakdown'  => $breakdown,
        ];
    }

    /**
     * Compute and persist the auto_score on an Evaluation.
     * Also recalculates final_score + grade band.
     *
     * @return array{auto_score: float, breakdown: array}
     */
    public function computeAndSave(Evaluation $evaluation): array
    {
        $result = $this->compute($evaluation);

        $evaluation->update(['auto_score' => $result['auto_score']]);

        // Recalculate final score using the existing formula
        $this->recalculateFinalScore($evaluation->fresh());

        return $result;
    }

    // =========================================================================
    // Component Calculators
    // =========================================================================

    /**
     * Task Completion Score
     *
     * Finds all tasks belonging to this employee that overlap with the
     * evaluation period date range. Calculates weighted average of
     * completion_pct, with a penalty for overdue incomplete tasks.
     */
    protected function computeTaskScore(Employee $employee, EvaluationPeriod $period, float $overduePenalty): array
    {
        $tasks = Task::where('employee_id', $employee->id)
            ->where(function ($q) use ($period) {
                // Task overlaps with period: task start ≤ period end AND task due ≥ period start
                $q->where(function ($inner) use ($period) {
                    $inner->whereNull('starting_date')
                        ->orWhere('starting_date', '<=', $period->end_date);
                })->where(function ($inner) use ($period) {
                    $inner->whereNull('due_date')
                        ->orWhere('due_date', '>=', $period->start_date);
                });
            })
            ->get();

        if ($tasks->isEmpty()) {
            return [
                'score'     => 0,
                'has_data'  => false,
                'total'     => 0,
                'completed' => 0,
                'overdue'   => 0,
                'avg_pct'   => 0,
            ];
        }

        $totalWeight   = $tasks->sum('weight') ?: $tasks->count();
        $weightedSum   = 0;
        $completedCount = 0;
        $overdueCount   = 0;

        foreach ($tasks as $task) {
            $taskWeight = ($task->weight > 0) ? $task->weight : 1;
            $pct = (float) $task->completion_pct;

            // Count completed
            if ($task->status?->value === 'completed' || $pct >= 100) {
                $completedCount++;
            }

            // Check overdue: due_date past AND not completed
            if ($task->due_date
                && $task->due_date->lt(now())
                && $task->status?->value !== 'completed'
                && $pct < 100
            ) {
                $overdueCount++;
            }

            $weightedSum += ($pct * $taskWeight);
        }

        $avgPct = $weightedSum / $totalWeight;

        // Apply overdue penalty
        $penalty = $overdueCount * $overduePenalty;
        $score   = max(0, min(100, $avgPct - $penalty));

        return [
            'score'     => round($score, 2),
            'has_data'  => true,
            'total'     => $tasks->count(),
            'completed' => $completedCount,
            'overdue'   => $overdueCount,
            'avg_pct'   => round($avgPct, 2),
            'penalty'   => round($penalty, 2),
        ];
    }

    /**
     * Deliverable Completion Score
     *
     * Finds deliverables for this user within fortnights that overlap with
     * the evaluation period. Score = (completed / total) × 100.
     */
    protected function computeDeliverableScore(Employee $employee, EvaluationPeriod $period): array
    {
        $userId = $employee->user_id;

        if (!$userId) {
            return ['score' => 0, 'has_data' => false, 'total' => 0, 'completed' => 0];
        }

        $deliverables = Deliverable::where('user_id', $userId)
            ->whereHas('fortnight', function ($q) use ($period) {
                $q->where('start_date', '<=', $period->end_date)
                  ->where('end_date', '>=', $period->start_date);
            })
            ->get();

        if ($deliverables->isEmpty()) {
            return ['score' => 0, 'has_data' => false, 'total' => 0, 'completed' => 0];
        }

        $total     = $deliverables->count();
        $completed = $deliverables->where('is_completed', true)->count();
        $score     = ($completed / $total) * 100;

        return [
            'score'     => round($score, 2),
            'has_data'  => true,
            'total'     => $total,
            'completed' => $completed,
        ];
    }

    /**
     * KPI Achievement Score
     *
     * Uses evaluation_ratings for the current evaluation — the average
     * of all rating scores across all KPIs assigned to this employee.
     * Only confirmed KPIs are counted.
     */
    protected function computeKpiScore(Evaluation $evaluation, Employee $employee): array
    {
        $ratings = EvaluationRating::where('evaluation_id', $evaluation->id)
            ->whereHas('kpi', function ($q) {
                $q->whereNotNull('confirmed_by');
            })
            ->get();

        if ($ratings->isEmpty()) {
            // Fall back: check if employee has any KPI assignments at all
            $assignedKpis = $employee->kpis()->confirmed()->count();
            return [
                'score'       => 0,
                'has_data'    => false,
                'rated_count' => 0,
                'avg_rating'  => 0,
                'assigned'    => $assignedKpis,
            ];
        }

        $avgScore = $ratings->avg('score');

        return [
            'score'       => round($avgScore, 2),
            'has_data'    => true,
            'rated_count' => $ratings->count(),
            'avg_rating'  => round($avgScore, 2),
        ];
    }

    /**
     * Attendance Score
     *
     * Finds attendance records whose payroll period overlaps with the
     * evaluation period. Score = 100 − (absent_days × 5) − (late_minutes / 60 × 2).
     * Floored at 0, capped at 100.
     */
    protected function computeAttendanceScore(Employee $employee, EvaluationPeriod $period): array
    {
        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->with('payrollPeriod')
            ->get()
            ->filter(function (AttendanceRecord $rec) use ($period) {
                $pp = $rec->payrollPeriod;
                if (!$pp || !$period->start_date || !$period->end_date) {
                    return false;
                }
                return $pp->start_date <= $period->end_date
                    && $pp->end_date   >= $period->start_date;
            });

        if ($records->isEmpty()) {
            return [
                'score'        => 0,
                'has_data'     => false,
                'absent_days'  => 0,
                'late_minutes' => 0,
            ];
        }

        $absentDays  = (int) $records->sum('absent_days');
        $lateMinutes = (int) $records->sum('late_minutes');

        $score = 100 - ($absentDays * 5) - ($lateMinutes / 60 * 2);
        $score = max(0, min(100, $score));

        return [
            'score'        => round($score, 2),
            'has_data'     => true,
            'absent_days'  => $absentDays,
            'late_minutes' => $lateMinutes,
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build the breakdown array for JSON response.
     */
    protected function buildBreakdown(array $components, array $configuredWeights, ?array $effectiveWeights = null): array
    {
        $breakdown = [];

        foreach ($components as $key => $data) {
            $breakdown[$key] = array_merge($data, [
                'configured_weight' => $configuredWeights[$key] ?? 0,
                'effective_weight'  => $effectiveWeights[$key] ?? ($data['has_data'] ? $configuredWeights[$key] : 0),
            ]);
        }

        return $breakdown;
    }

    /**
     * Recalculate final_score + grade_band using the same formula as
     * PerformanceCrudController::recalculateEvaluationScore().
     */
    protected function recalculateFinalScore(Evaluation $eval): void
    {
        $auto    = $eval->auto_score ?? 0;
        $manager = $eval->manager_score ?? 0;
        $exec    = $eval->executive_score ?? 0;

        $wAuto    = (float) Setting::get('weight_auto_score', 0.40);
        $wManager = (float) Setting::get('weight_manager_score', 0.40);
        $wExec    = (float) Setting::get('weight_executive_score', 0.20);

        $final = ($auto * $wAuto) + ($manager * $wManager) + ($exec * $wExec);

        $band = \App\Models\GradeBand::where('min_score', '<=', $final)
            ->where('max_score', '>=', $final)
            ->first();

        $eval->update([
            'final_score'   => round($final, 2),
            'grade_band_id' => $band?->id,
        ]);

        // Handle increment recommendation
        if ($band && $band->triggers_increment) {
            $employee = $eval->employee;
            if ($employee && $employee->base_salary > 0) {
                $inc      = $band->increment_pct / 100;
                $proposed = $employee->base_salary * (1 + $inc);

                \App\Models\IncrementRecommendation::updateOrCreate(
                    ['evaluation_id' => $eval->id],
                    [
                        'current_salary'  => $employee->base_salary,
                        'proposed_salary' => $proposed,
                        'status'          => 'pending',
                    ]
                );
            }
        } else {
            \App\Models\IncrementRecommendation::where('evaluation_id', $eval->id)->delete();
        }
    }
}
