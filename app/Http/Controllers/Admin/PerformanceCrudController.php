<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\Task;
use App\Models\Deliverable;
use App\Models\EvaluationPeriod;
use App\Models\Evaluation;
use App\Models\EvaluationRating;
use App\Models\NarrativeReport;
use App\Models\AiAnalysis;
use App\Models\GradeScale;
use App\Models\GradeBand;
use App\Models\IncrementRecommendation;
use App\Models\Setting;
use App\Enums\KpiStatus;
use App\Enums\MeasureType;
use App\Jobs\AnalyzeNarrativeReportJob;
use App\Jobs\GeneratePerformanceInsightJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PerformanceCrudController extends Controller
{
    // ==========================================
    // KPIs MAKER-CHECKER FLOW
    // ==========================================

    public function storeKpi(Request $request)
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'measure_type' => ['required', 'string'],
            'target_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'kpiable_type' => ['nullable', 'string'],
            'kpiable_id' => ['nullable', 'integer'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
        ]);

        DB::transaction(function () use ($data) {
            $kpi = Kpi::create([
                'title_en' => $data['title_en'],
                'title_am' => $data['title_am'] ?? null,
                'measure_type' => $data['measure_type'],
                'target_value' => $data['target_value'] ?? null,
                'unit' => $data['unit'] ?? null,
                'weight' => $data['weight'],
                'kpiable_type' => $data['kpiable_type'] ?? null,
                'kpiable_id' => $data['kpiable_id'] ?? null,
                'status' => 'created',
            ]);

            if (!empty($data['employee_ids'])) {
                $kpi->employees()->sync($data['employee_ids']);
            }
        });

        return redirect()->back()->with('success', "KPI created successfully and is awaiting maker-checker approvals.");
    }

    public function updateKpi(Request $request, Kpi $kpi)
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'measure_type' => ['required', 'string'],
            'target_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0'],
            'kpiable_type' => ['nullable', 'string'],
            'kpiable_id' => ['nullable', 'integer'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
        ]);

        DB::transaction(function () use ($kpi, $data) {
            $kpi->update([
                'title_en' => $data['title_en'],
                'title_am' => $data['title_am'] ?? null,
                'measure_type' => $data['measure_type'],
                'target_value' => $data['target_value'] ?? null,
                'unit' => $data['unit'] ?? null,
                'weight' => $data['weight'],
                'kpiable_type' => $data['kpiable_type'] ?? null,
                'kpiable_id' => $data['kpiable_id'] ?? null,
            ]);

            if (isset($data['employee_ids'])) {
                $kpi->employees()->sync($data['employee_ids']);
            }
        });

        return redirect()->back()->with('success', "KPI updated successfully.");
    }

    public function approveKpi(Kpi $kpi)
    {
        $kpi->update([
            'approved_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "KPI approved (Maker step completed).");
    }

    public function confirmKpi(Kpi $kpi)
    {
        // Maker-checker order: a KPI must be approved (maker) before it can be
        // confirmed (checker). Only confirmed KPIs count toward scores.
        if (! $kpi->approved_by) {
            return redirect()->back()->with('error', "This KPI must be approved (maker step) before it can be confirmed.");
        }

        $kpi->update([
            'confirmed_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "KPI confirmed (Checker step completed).");
    }

    public function destroyKpi(Kpi $kpi)
    {
        $kpi->delete();
        return redirect()->back()->with('success', "KPI deleted successfully.");
    }

    // ==========================================
    // TASKS CRUD
    // ==========================================

    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'target_id' => ['nullable', 'exists:targets,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cadence' => ['required', 'string'],
            'starting_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:starting_date'],
            'weight' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'completion_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['created_by'] = auth()->id();
        $data['assigned_by_id'] = auth()->id();

        Task::create($data);

        return redirect()->back()->with('success', "Task created successfully.");
    }

    public function updateTask(Request $request, Task $task)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'target_id' => ['nullable', 'exists:targets,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cadence' => ['required', 'string'],
            'starting_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:starting_date'],
            'weight' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'completion_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $task->update($data);

        return redirect()->back()->with('success', "Task updated successfully.");
    }

    public function destroyTask(Task $task)
    {
        $task->delete();
        return redirect()->back()->with('success', "Task deleted successfully.");
    }

    // ==========================================
    // DELIVERABLES CRUD
    // ==========================================

    public function storeDeliverable(Request $request)
    {
        $data = $request->validate([
            'fortnight_id' => ['required', 'exists:fortnights,id'],
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'is_completed' => ['boolean'],
        ]);

        Deliverable::create($data);

        return redirect()->back()->with('success', "Deliverable created successfully.");
    }

    public function updateDeliverable(Request $request, Deliverable $deliverable)
    {
        $data = $request->validate([
            'fortnight_id' => ['required', 'exists:fortnights,id'],
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'is_completed' => ['boolean'],
        ]);

        $deliverable->update($data);

        return redirect()->back()->with('success', "Deliverable updated successfully.");
    }

    public function toggleDeliverable(Deliverable $deliverable)
    {
        $completed = !$deliverable->is_completed;
        $deliverable->update([
            'is_completed' => $completed,
            'reviewed_by' => $completed ? auth()->id() : null,
        ]);

        return redirect()->back()->with('success', "Deliverable status updated.");
    }

    public function destroyDeliverable(Deliverable $deliverable)
    {
        $deliverable->delete();
        return redirect()->back()->with('success', "Deliverable deleted successfully.");
    }

    // ==========================================
    // EVALUATIONS PERIODS & MULTI-RATER CALCULATIONS
    // ==========================================

    public function storePeriod(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cadence' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'formula_version' => ['nullable', 'string'],
        ]);

        EvaluationPeriod::create($data);

        return redirect()->back()->with('success', "Evaluation Period created.");
    }

    public function generateMonthlyPeriods(Request $request)
    {
        $request->validate([
            'year_id' => ['required', 'exists:years,id'],
        ]);

        $year = \App\Models\Year::findOrFail($request->year_id);

        if (!$year->start_date || !$year->end_date) {
            return redirect()->back()->with('error', "The Fiscal Year must have a start and end date before generating periods.");
        }

        DB::transaction(function () use ($year) {
            $start = \Carbon\Carbon::parse($year->start_date);
            $end = \Carbon\Carbon::parse($year->end_date);

            // 1. Generate Quarters & Fortnights if they don't exist
            if ($year->quarters()->count() === 0) {
                // Generate 4 Quarters
                for ($i = 1; $i <= 4; $i++) {
                    $qStart = $start->copy()->addMonths(($i - 1) * 3);
                    $qEnd = $start->copy()->addMonths($i * 3)->subDay();

                    // Cap the last quarter at the year's end date
                    if ($i === 4 || $qEnd->gt($end)) {
                        $qEnd = $end->copy();
                    }

                    $quarter = \App\Models\Quarter::create([
                        'year_id' => $year->id,
                        'name' => "Q{$i}",
                        'start_date' => $qStart->toDateString(),
                        'end_date' => $qEnd->toDateString(),
                    ]);

                    // Generate Fortnights for this Quarter
                    $fStart = $qStart->copy();
                    $fIndex = 1;

                    while ($fStart->lt($qEnd)) {
                        $fEnd = $fStart->copy()->addDays(13);
                        if ($fEnd->gt($qEnd)) {
                            $fEnd = $qEnd->copy();
                        }

                        $fortnightName = "Q{$i}-F{$fIndex}";
                        $fortnight = \App\Models\Fortnight::create([
                            'quarter_id' => $quarter->id,
                            'name' => $fortnightName,
                            'start_date' => $fStart->toDateString(),
                            'end_date' => $fEnd->toDateString(),
                        ]);

                        // Generate Days for this Fortnight
                        $dayDate = $fStart->copy();
                        while ($dayDate->lte($fEnd)) {
                            \App\Models\Day::updateOrCreate(
                                ['date' => $dayDate->copy()->startOfDay()],
                                ['fortnight_id' => $fortnight->id]
                            );
                            $dayDate->addDay();
                        }

                        $fStart->addDays(14);
                        $fIndex++;
                    }
                }
            }

            // 2. Generate 12 months starting from fiscal year's start date
            for ($i = 0; $i < 12; $i++) {
                $currentMonth = $start->copy()->addMonths($i);
                $monthStart = $currentMonth->copy()->startOfMonth();
                $monthEnd = $currentMonth->copy()->endOfMonth();
                $name = $monthStart->format('F Y');

                // Create EvaluationPeriod
                EvaluationPeriod::firstOrCreate([
                    'name' => $name,
                ], [
                    'cadence' => 'monthly',
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthEnd->toDateString(),
                    'status' => 'open',
                ]);

                // Create PayrollPeriod
                \App\Models\PayrollPeriod::firstOrCreate([
                    'name' => $name,
                ], [
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthEnd->toDateString(),
                    'status' => 'open',
                    'payment_date' => $monthEnd->toDateString(),
                ]);
            }

            // 3. Generate fortnightly evaluation and payroll periods
            $fortnights = \App\Models\Fortnight::whereIn('quarter_id', $year->quarters()->pluck('id'))->get();
            foreach ($fortnights as $fortnight) {
                EvaluationPeriod::firstOrCreate([
                    'name' => $fortnight->name,
                ], [
                    'cadence' => 'fortnightly',
                    'start_date' => $fortnight->start_date->toDateString(),
                    'end_date' => $fortnight->end_date->toDateString(),
                    'status' => 'open',
                ]);

                \App\Models\PayrollPeriod::firstOrCreate([
                    'name' => $fortnight->name,
                ], [
                    'start_date' => $fortnight->start_date->toDateString(),
                    'end_date' => $fortnight->end_date->toDateString(),
                    'status' => 'open',
                    'payment_date' => $fortnight->end_date->toDateString(),
                ]);
            }
        });

        return redirect()->back()->with('success', "Evaluation and Payroll periods (including monthly and fortnightly cadences) generated successfully for {$year->label}.");
    }

    public function togglePeriodStatus(EvaluationPeriod $period)
    {
        $period->update([
            'status' => $period->status === 'open' ? 'locked' : 'open',
        ]);

        return redirect()->back()->with('success', "Evaluation Period status toggled.");
    }

    public function storeEvaluation(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'evaluation_period_id' => ['required', 'exists:evaluation_periods,id'],
            'auto_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'manager_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'executive_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $eval = Evaluation::create($data);
        $this->recalculateEvaluationScore($eval);

        return redirect()->back()->with('success', "Evaluation scorecard created.");
    }

    public function updateEvaluation(Request $request, Evaluation $evaluation)
    {
        $data = $request->validate([
            'auto_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'manager_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'executive_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $evaluation->update($data);
        $this->recalculateEvaluationScore($evaluation);

        return redirect()->back()->with('success', "Evaluation scorecard updated.");
    }

    protected function recalculateEvaluationScore(Evaluation $eval)
    {
        $auto = $eval->auto_score ?? 0;
        $manager = $eval->manager_score ?? 0;
        $exec = $eval->executive_score ?? 0;

        $wAuto = (float) Setting::get('weight_auto_score', 0.40);
        $wManager = (float) Setting::get('weight_manager_score', 0.40);
        $wExec = (float) Setting::get('weight_executive_score', 0.20);

        // Dynamic formula
        $final = ($auto * $wAuto) + ($manager * $wManager) + ($exec * $wExec);
        
        // Find matching grade band
        $band = GradeBand::where('min_score', '<=', $final)
            ->where('max_score', '>=', $final)
            ->first();

        $eval->update([
            'final_score' => $final,
            'grade_band_id' => $band ? $band->id : null,
        ]);

        // Generate increment recommendation if band triggers increment
        if ($band && $band->triggers_increment) {
            $employee = $eval->employee;
            if ($employee && $employee->base_salary > 0) {
                $inc = $band->increment_pct / 100;
                $proposed = $employee->base_salary * (1 + $inc);

                IncrementRecommendation::updateOrCreate(
                    ['evaluation_id' => $eval->id],
                    [
                        'current_salary' => $employee->base_salary,
                        'proposed_salary' => $proposed,
                        'status' => 'pending',
                    ]
                );
            }
        } else {
            IncrementRecommendation::where('evaluation_id', $eval->id)->delete();
        }
    }

    // ==========================================
    // GRADING & INCREMENTS
    // ==========================================

    public function storeGradeScale(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        GradeScale::create($data);

        return redirect()->back()->with('success', "Grade Scale created.");
    }

    public function storeGradeBand(Request $request)
    {
        $data = $request->validate([
            'grade_scale_id' => ['required', 'exists:grade_scales,id'],
            'label_en' => ['required', 'string', 'max:255'],
            'label_am' => ['nullable', 'string', 'max:255'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100', 'greater_than_field:min_score'],
            'triggers_increment' => ['boolean'],
            'increment_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        GradeBand::create($data);

        return redirect()->back()->with('success', "Grade Band created.");
    }

    public function approveIncrement(IncrementRecommendation $recommendation)
    {
        DB::transaction(function () use ($recommendation) {
            $recommendation->update([
                'status' => 'approved',
                'approved_by_id' => auth()->id(),
            ]);

            // Apply salary update to Employee
            $eval = $recommendation->evaluation;
            if ($eval && $eval->employee) {
                $eval->employee->update([
                    'base_salary' => $recommendation->proposed_salary,
                ]);
            }
        });

        return redirect()->back()->with('success', "Increment recommendation approved and applied to employee salary.");
    }

    public function storeRating(Request $request)
    {
        $data = $request->validate([
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'rater_user_id' => ['required', 'exists:users,id'],
            'rater_type' => ['required', 'string'],
            'kpi_id' => ['required', 'exists:kpis,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'comment_en' => ['nullable', 'string'],
            'comment_am' => ['nullable', 'string'],
        ]);

        EvaluationRating::create([
            'evaluation_id' => $data['evaluation_id'],
            'rater_user_id' => $data['rater_user_id'],
            'rater_type' => $data['rater_type'],
            'kpi_id' => $data['kpi_id'],
            'score' => $data['score'],
            'comment_en' => $data['comment_en'] ?? null,
            'comment_am' => $data['comment_am'] ?? null,
        ]);

        return redirect()->back()->with('success', "Rating recorded successfully.");
    }

    public function updateRating(Request $request, EvaluationRating $rating)
    {
        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'comment_en' => ['nullable', 'string'],
            'comment_am' => ['nullable', 'string'],
        ]);

        $rating->update([
            'score' => $data['score'],
            'comment_en' => $data['comment_en'] ?? null,
            'comment_am' => $data['comment_am'] ?? null,
        ]);

        return redirect()->back()->with('success', "Rating updated successfully.");
    }

    public function destroyRating(EvaluationRating $rating)
    {
        $rating->delete();
        return redirect()->back()->with('success', "Rating deleted successfully.");
    }

    public function storeNarrativeReport(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'evaluation_period_id' => ['required', 'exists:evaluation_periods,id'],
            'language' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $report = NarrativeReport::create($data);

        // Run synchronously in local env (no worker needed); async in production
        app()->environment('local')
            ? AnalyzeNarrativeReportJob::dispatchSync($report)
            : AnalyzeNarrativeReportJob::dispatch($report);

        return redirect()->back()->with('success', "Narrative Report submitted and AI analysis complete.");
    }

    // ==========================================
    // AI ANALYSIS ACTIONS
    // ==========================================

    /**
     * Trigger AI narrative analysis for a specific report (AJAX).
     */
    public function triggerNarrativeAnalysis(NarrativeReport $report)
    {
        // Local: run inline so the result is ready before the response returns.
        // Production: hand off to a queue worker.
        if (app()->environment('local')) {
            AnalyzeNarrativeReportJob::dispatchSync($report);
        } else {
            AnalyzeNarrativeReportJob::dispatch($report);
        }

        // Return the freshly-created analysis so the Vue page can display
        // it immediately without a full reload.
        $analysis = AiAnalysis::with(['narrativeReport.employee', 'confirmedBy'])
            ->where('narrative_report_id', $report->id)
            ->latest()
            ->first();

        return response()->json([
            'message'   => app()->environment('local') ? 'Analysis complete.' : 'Narrative analysis queued.',
            'report_id' => $report->id,
            'analysis'  => $analysis,
        ]);
    }

    /**
     * Trigger deep performance insight generation for an evaluation (AJAX).
     */
    public function triggerPerformanceAnalysis(Evaluation $evaluation)
    {
        if (app()->environment('local')) {
            GeneratePerformanceInsightJob::dispatchSync($evaluation);
        } else {
            GeneratePerformanceInsightJob::dispatch($evaluation);
        }

        // After sync execution, find the analysis that was just written.
        $narrativeReport = \App\Models\NarrativeReport::where('employee_id', $evaluation->employee_id)
            ->where('evaluation_period_id', $evaluation->evaluation_period_id)
            ->latest()->first();

        $analysis = $narrativeReport
            ? AiAnalysis::with(['narrativeReport.employee', 'confirmedBy'])
                ->where('narrative_report_id', $narrativeReport->id)
                ->latest()->first()
            : null;

        return response()->json([
            'message'       => app()->environment('local') ? 'Performance analysis complete.' : 'Performance analysis queued.',
            'evaluation_id' => $evaluation->id,
            'analysis'      => $analysis,
        ]);
    }

    /**
     * Human-confirm an AI analysis result.
     */
    public function confirmAnalysis(AiAnalysis $analysis)
    {
        $analysis->update([
            'human_confirmed' => true,
            'confirmed_by_id' => auth()->id(),
        ]);

        return response()->json([
            'message'          => 'Analysis confirmed.',
            'analysis_id'      => $analysis->id,
            'human_confirmed'  => true,
        ]);
    }

    /**
     * Dismiss (delete) an AI analysis result.
     */
    public function dismissAnalysis(AiAnalysis $analysis)
    {
        $analysis->delete();

        return response()->json(['message' => 'Analysis dismissed.']);
    }

    // ==========================================
    // AUTO SCORE COMPUTATION
    // ==========================================

    /**
     * Compute the auto_score for a single evaluation from system data.
     * Returns the breakdown so the frontend can display transparency.
     */
    public function computeAutoScore(Evaluation $evaluation)
    {
        $calculator = app(\App\Services\AutoScoreCalculator::class);
        $result     = $calculator->computeAndSave($evaluation);

        // Reload to get fresh final_score + grade_band
        $evaluation->load(['employee', 'period', 'gradeBand']);

        return response()->json([
            'message'        => 'Auto score computed from system data.',
            'evaluation_id'  => $evaluation->id,
            'auto_score'     => $result['auto_score'],
            'final_score'    => $evaluation->final_score,
            'grade_band'     => $evaluation->gradeBand?->label_en,
            'breakdown'      => $result['breakdown'],
        ]);
    }

    /**
     * Batch-compute auto_scores for ALL evaluations in a period.
     */
    public function computeAllAutoScores(EvaluationPeriod $period)
    {
        $calculator  = app(\App\Services\AutoScoreCalculator::class);
        $evaluations = $period->evaluations()->with('employee')->get();
        $results     = [];

        foreach ($evaluations as $eval) {
            $result    = $calculator->computeAndSave($eval);
            $results[] = [
                'evaluation_id' => $eval->id,
                'employee_name' => $eval->employee?->full_name_en ?? '—',
                'auto_score'    => $result['auto_score'],
            ];
        }

        return response()->json([
            'message'  => 'Auto scores computed for ' . count($results) . ' evaluation(s).',
            'period'   => $period->name,
            'results'  => $results,
        ]);
    }
}


