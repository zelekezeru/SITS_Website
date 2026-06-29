<?php

namespace App\Services\Ai;

use App\Models\Evaluation;
use App\Models\AttendanceRecord;
use App\Models\NarrativeReport;

/**
 * PerformanceContextBuilder
 *
 * Aggregates every available data point for an Evaluation into a single
 * structured array that is serialized into the AI performance prompt.
 *
 * Eager-loads all relations in as few queries as possible, then shapes
 * the data into a clean, provider-agnostic context object.
 */
class PerformanceContextBuilder
{
    /**
     * Build a complete context array for the given Evaluation.
     *
     * @return array<string, mixed>
     */
    public function build(Evaluation $evaluation): array
    {
        // One eager-load covers almost everything
        $evaluation->loadMissing([
            'employee.position',
            'employee.department',
            'period',
            'gradeBand',
            'ratings.kpi',
            'ratings.rater',
            'incrementRecommendation',
        ]);

        $employee = $evaluation->employee;
        $period   = $evaluation->period;

        return [
            'evaluation'     => $this->buildEvaluationBlock($evaluation),
            'employee'       => $this->buildEmployeeBlock($employee),
            'period'         => $this->buildPeriodBlock($period),
            'kpi_ratings'    => $this->buildKpiRatingsBlock($evaluation),
            'attendance'     => $this->buildAttendanceBlock($employee, $period),
            'narrative'      => $this->buildNarrativeBlock($employee, $period),
            'tasks'          => $this->buildTasksBlock($employee),
            'increment'      => $this->buildIncrementBlock($evaluation),
        ];
    }

    // =========================================================================
    // Block builders
    // =========================================================================

    protected function buildEvaluationBlock(Evaluation $eval): array
    {
        return [
            'id'              => $eval->id,
            'auto_score'      => $eval->auto_score      !== null ? (float) $eval->auto_score      : null,
            'manager_score'   => $eval->manager_score   !== null ? (float) $eval->manager_score   : null,
            'executive_score' => $eval->executive_score !== null ? (float) $eval->executive_score : null,
            'final_score'     => $eval->final_score     !== null ? (float) $eval->final_score     : null,
            'grade_band'      => $eval->gradeBand ? [
                'label_en'          => $eval->gradeBand->label_en,
                'label_am'          => $eval->gradeBand->label_am,
                'min_score'         => (float) $eval->gradeBand->min_score,
                'max_score'         => (float) $eval->gradeBand->max_score,
                'triggers_increment'=> (bool)  $eval->gradeBand->triggers_increment,
                'increment_pct'     => (float) $eval->gradeBand->increment_pct,
            ] : null,
            'status' => $eval->status?->value ?? $eval->status,
        ];
    }

    protected function buildEmployeeBlock($employee): array
    {
        if (!$employee) {
            return [];
        }

        return [
            'id'              => $employee->id,
            'full_name_en'    => $employee->full_name_en,
            'full_name_am'    => $employee->full_name_am,
            'staff_no'        => $employee->staff_no,
            'position'        => $employee->position?->title_en ?? null,
            'department'      => $employee->department?->name_en ?? null,
            'employment_type' => $employee->employment_type?->value ?? $employee->employment_type,
            'base_salary'     => $employee->base_salary !== null ? (float) $employee->base_salary : null,
            'hired_at'        => $employee->hired_at?->toDateString(),
            'status'          => $employee->status?->value ?? $employee->status,
        ];
    }

    protected function buildPeriodBlock($period): array
    {
        if (!$period) {
            return [];
        }

        return [
            'id'         => $period->id,
            'name'       => $period->name,
            'cadence'    => $period->cadence?->value ?? $period->cadence,
            'start_date' => $period->start_date?->toDateString(),
            'end_date'   => $period->end_date?->toDateString(),
            'status'     => $period->status?->value ?? $period->status,
        ];
    }

    protected function buildKpiRatingsBlock(Evaluation $eval): array
    {
        $grouped = [];

        foreach ($eval->ratings as $rating) {
            $kpiId    = $rating->kpi_id;
            $kpiTitle = $rating->kpi?->title_en ?? "KPI #{$kpiId}";

            if (!isset($grouped[$kpiId])) {
                $grouped[$kpiId] = [
                    'kpi_id'       => $kpiId,
                    'kpi_title_en' => $kpiTitle,
                    'kpi_title_am' => $rating->kpi?->title_am,
                    'weight'       => $rating->kpi ? (float) $rating->kpi->weight       : null,
                    'target_value' => $rating->kpi ? (float) $rating->kpi->target_value : null,
                    'unit'         => $rating->kpi?->unit,
                    'measure_type' => $rating->kpi?->measure_type?->value ?? $rating->kpi?->measure_type,
                    'ratings'      => [],
                ];
            }

            $grouped[$kpiId]['ratings'][] = [
                'rater_type' => $rating->rater_type?->value ?? $rating->rater_type,
                'rater_name' => $rating->rater?->name ?? 'Unknown',
                'score'      => (float) $rating->score,
                'comment_en' => $rating->comment_en,
                'comment_am' => $rating->comment_am,
            ];
        }

        return array_values($grouped);
    }

    protected function buildAttendanceBlock($employee, $period): array
    {
        if (!$employee || !$period) {
            return [];
        }

        // AttendanceRecord is linked to payroll_period, not evaluation_period.
        // Best-effort: fetch all records for this employee and filter by date overlap.
        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->with('payrollPeriod')
            ->get()
            ->filter(function (AttendanceRecord $rec) use ($period) {
                $pp = $rec->payrollPeriod;
                if (!$pp || !$period->start_date || !$period->end_date) {
                    return false;
                }
                // Overlap check
                return $pp->start_date <= $period->end_date
                    && $pp->end_date   >= $period->start_date;
            });

        if ($records->isEmpty()) {
            return [];
        }

        return [
            'work_hours'     => (float) $records->sum('work_hours'),
            'late_minutes'   => (int)   $records->sum('late_minutes'),
            'absent_days'    => (int)   $records->sum('absent_days'),
            'permitted_days' => (int)   $records->sum('permitted_days'),
            'overtime_normal'=> (float) $records->sum('overtime_normal'),
            'ot_night'       => (float) $records->sum('ot_night'),
            'ot_rest'        => (float) $records->sum('ot_rest'),
            'ot_holiday'     => (float) $records->sum('ot_holiday'),
            'record_count'   => $records->count(),
        ];
    }

    protected function buildNarrativeBlock($employee, $period): array
    {
        if (!$employee || !$period) {
            return [];
        }

        $report = NarrativeReport::where('employee_id', $employee->id)
            ->where('evaluation_period_id', $period->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$report) {
            return [];
        }

        return [
            'id'       => $report->id,
            'language' => $report->language?->value ?? $report->language,
            'body'     => $report->body,
        ];
    }

    protected function buildTasksBlock($employee): array
    {
        if (!$employee) {
            return [];
        }

        $tasks = $employee->tasks()
            ->select(['id', 'title', 'status', 'completion_pct', 'due_date', 'cadence'])
            ->orderByDesc('due_date')
            ->limit(20)
            ->get();

        return $tasks->map(fn ($t) => [
            'id'             => $t->id,
            'title'          => $t->title,
            'status'         => $t->status?->value ?? $t->status,
            'completion_pct' => (float) $t->completion_pct,
            'due_date'       => $t->due_date?->toDateString(),
            'cadence'        => $t->cadence?->value ?? $t->cadence,
        ])->all();
    }

    protected function buildIncrementBlock(Evaluation $eval): array
    {
        $inc = $eval->incrementRecommendation;

        if (!$inc) {
            return [];
        }

        return [
            'current_salary'  => (float) $inc->current_salary,
            'proposed_salary' => (float) $inc->proposed_salary,
            'status'          => $inc->status?->value ?? $inc->status,
        ];
    }
}
