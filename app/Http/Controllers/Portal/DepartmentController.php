<?php

namespace App\Http\Controllers\Portal;

use App\Enums\Cadence;
use App\Enums\EvaluationStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Fortnight;
use App\Models\GradeBand;
use App\Models\IncrementRecommendation;
use App\Models\Kpi;
use App\Models\Setting;
use App\Models\Target;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Department Head workspace. Everything is scoped to the head's managed
 * departments (departments they head + their own). The route is guarded by
 * role.landing:Department Head; this controller adds the fine-grained
 * department-ownership checks on top of the spatie permission abilities.
 */
class DepartmentController extends Controller
{
    // ===================== PAGES =====================

    public function team(Request $request): Response
    {
        $deptIds = $this->deptIds($request);

        $employees = Employee::whereIn('department_id', $deptIds)
            ->with(['position:id,title_en,title_am', 'department:id,name_en', 'user:id,name,email'])
            ->withCount([
                'tasks as open_tasks_count' => fn ($q) => $q->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress]),
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Completed),
                'kpis as kpis_count',
            ])
            ->orderBy('full_name_en')
            ->get();

        $latest = Evaluation::whereIn('employee_id', $employees->pluck('id'))
            ->orderByDesc('created_at')->get()->groupBy('employee_id');

        $rows = $employees->map(function ($e) use ($latest) {
            $arr = $e->toArray();
            $arr['latest_score'] = optional($latest->get($e->id)?->first())->final_score;
            return $arr;
        });

        return Inertia::render('Department/Team', [
            'team' => $rows,
            'summary' => [
                'total' => $employees->count(),
                'active' => $employees->where('is_active', true)->count(),
                'open_tasks' => (int) $employees->sum('open_tasks_count'),
            ],
        ]);
    }

    public function archive(Request $request): Response
    {
        $departments = $this->headedDepartments($request)
            ->with('documents.uploadedBy')
            ->orderBy('name_en')
            ->get(['id', 'name_en']);

        return Inertia::render('Department/Archive', [
            'departments' => $departments,
        ]);
    }

    public function storeDocument(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer'],
            'file_path' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:51200'],
        ]);

        $this->guardHeadsDepartment($request, (int) $data['department_id']);

        if (! $request->hasFile('file') && ! $request->filled('file_path')) {
            return redirect()->back()->withErrors(['file' => 'A file upload or web link is required.']);
        }

        \App\Support\DocumentUploader::store(
            title: $data['name'],
            documentableType: Department::class,
            documentableId: (int) $data['department_id'],
            file: $request->file('file'),
            filePath: $data['file_path'] ?? null,
            uploadedBy: $request->user()->id,
        );

        return redirect()->back()->with('success', 'Document added to the department archive.');
    }

    public function destroyDocument(Request $request, Document $document)
    {
        abort_unless($document->documentable_type === Department::class, 404);
        $this->guardHeadsDepartment($request, $document->documentable_id);

        $document->delete();

        return redirect()->back()->with('success', 'Document removed from the department archive.');
    }

    public function tasks(Request $request): Response
    {
        $employeeIds = $this->employeeIds($request);

        $order = ['in_progress' => 0, 'pending' => 1, 'submitted' => 2, 'completed' => 3, 'missed' => 4];
        $tasks = Task::whereIn('employee_id', $employeeIds)
            ->with(['employee:id,full_name_en,position_id', 'employee.position:id,title_en', 'target:id,name'])
            ->get()
            ->sortBy(fn (Task $t) => $t->due_date?->getTimestamp() ?? PHP_INT_MAX)
            ->sortBy(fn (Task $t) => $order[$t->status->value] ?? 9)
            ->values();

        return Inertia::render('Department/Tasks', [
            'tasks' => $tasks,
            'team' => Employee::whereIn('id', $employeeIds)
                ->with('position:id,title_en')->orderBy('full_name_en')->get(['id', 'full_name_en', 'position_id']),
            'targets' => Target::orderBy('name')->get(['id', 'name']),
            'cadences' => $this->options(Cadence::cases()),
            'statuses' => $this->options(TaskStatus::cases()),
            'can' => ['manage' => $this->canManageTasks($request)],
        ]);
    }

    public function kpis(Request $request): Response
    {
        $employeeIds = $this->employeeIds($request);

        $kpis = Kpi::whereHas('employees', fn ($q) => $q->whereIn('employees.id', $employeeIds))
            ->with(['employees:id,full_name_en', 'approvedBy:id,name', 'confirmedBy:id,name', 'kpiable'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Department/Kpis', [
            'kpis' => $kpis,
            'can' => ['approve' => $request->user()->can('approve kpis')],
        ]);
    }

    public function evaluations(Request $request): Response
    {
        $employeeIds = $this->employeeIds($request);

        $evaluations = Evaluation::whereIn('employee_id', $employeeIds)
            ->with(['employee:id,full_name_en', 'period', 'gradeBand:id,label_en'])
            ->latest()
            ->get();

        return Inertia::render('Department/Evaluations', [
            'evaluations' => $evaluations,
            'team' => Employee::whereIn('id', $employeeIds)->orderBy('full_name_en')->get(['id', 'full_name_en']),
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'weights' => $this->weights(),
            'can' => ['score' => $request->user()->can('score evaluations')],
        ]);
    }

    public function deliverables(Request $request): Response
    {
        $userIds = $this->teamUserIds($request);

        $deliverables = Deliverable::whereIn('user_id', $userIds)
            ->with(['fortnight:id,name', 'user:id,name', 'reviewedBy:id,name'])
            ->orderByDesc('deadline')
            ->get();

        return Inertia::render('Department/Deliverables', [
            'deliverables' => $deliverables,
            'fortnights' => Fortnight::orderByDesc('start_date')->get(['id', 'name']),
            'people' => \App\Models\User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
            'can' => ['manage' => $request->user()->can('manage deliverables')],
        ]);
    }

    public function reports(Request $request): Response
    {
        $deptIds = $this->deptIds($request);
        $employees = Employee::whereIn('department_id', $deptIds)->get();
        $employeeIds = $employees->pluck('id');

        $tasks = Task::whereIn('employee_id', $employeeIds)->get();
        $evaluations = Evaluation::whereIn('employee_id', $employeeIds)->whereNotNull('final_score')->get();
        $kpis = Kpi::whereHas('employees', fn ($q) => $q->whereIn('employees.id', $employeeIds))->get();

        // Per-employee scorecard.
        $scorecards = $employees->map(function ($e) use ($tasks, $evaluations) {
            $own = $tasks->where('employee_id', $e->id);
            $completed = $own->where('status', TaskStatus::Completed)->count();
            $latestScore = $evaluations->where('employee_id', $e->id)->sortByDesc('created_at')->first()?->final_score;

            return [
                'id' => $e->id,
                'name' => $e->full_name_en,
                'tasks' => $own->count(),
                'completed' => $completed,
                'completion' => $own->count() ? round($completed / $own->count() * 100) : 0,
                'score' => $latestScore !== null ? round((float) $latestScore, 1) : null,
            ];
        })->values();

        return Inertia::render('Department/Reports', [
            'metrics' => [
                'team' => $employees->count(),
                'tasks_total' => $tasks->count(),
                'tasks_completed' => $tasks->where('status', TaskStatus::Completed)->count(),
                'completion_rate' => $tasks->count()
                    ? round($tasks->where('status', TaskStatus::Completed)->count() / $tasks->count() * 100)
                    : 0,
                'avg_score' => $evaluations->count() ? round((float) $evaluations->avg('final_score'), 1) : null,
                'kpis_confirmed' => $kpis->whereNotNull('confirmed_by')->count(),
                'kpis_total' => $kpis->count(),
            ],
            'kpiStatus' => [
                'created' => $kpis->whereNull('approved_by')->count(),
                'approved' => $kpis->whereNotNull('approved_by')->whereNull('confirmed_by')->count(),
                'confirmed' => $kpis->whereNotNull('confirmed_by')->count(),
            ],
            'scorecards' => $scorecards,
        ]);
    }

    // ===================== TASK MUTATIONS =====================

    public function storeTask(Request $request)
    {
        $data = $this->validateTask($request);
        $this->guardEmployeeInDept($request, (int) $data['employee_id']);

        $data['created_by'] = $request->user()->id;
        $data['assigned_by_id'] = $request->user()->id;
        Task::create($data);

        return back()->with('success', 'Task assigned to your team member.');
    }

    public function updateTask(Request $request, Task $task)
    {
        $this->guardTaskInDept($request, $task);
        $data = $this->validateTask($request);
        $this->guardEmployeeInDept($request, (int) $data['employee_id']);

        $task->update($data);

        return back()->with('success', 'Task updated.');
    }

    public function destroyTask(Request $request, Task $task)
    {
        $this->guardTaskInDept($request, $task);
        $task->delete();

        return back()->with('success', 'Task removed.');
    }

    // ===================== KPI MAKER STEP =====================

    public function approveKpi(Request $request, Kpi $kpi)
    {
        abort_unless($request->user()->can('approve kpis'), 403);
        $this->guardKpiInDept($request, $kpi);

        if ($kpi->isApproved()) {
            return back()->with('error', 'This KPI has already been approved.');
        }

        $kpi->update(['approved_by' => $request->user()->id]);

        return back()->with('success', 'KPI approved — it now awaits the President\'s confirmation.');
    }

    // ===================== EVALUATION SCORING =====================

    public function storeEvaluation(Request $request)
    {
        abort_unless($request->user()->can('score evaluations'), 403);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'evaluation_period_id' => ['required', 'exists:evaluation_periods,id'],
            'manager_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->guardEmployeeInDept($request, (int) $data['employee_id']);
        $this->guardPeriodOpen((int) $data['evaluation_period_id']);

        $eval = Evaluation::firstOrNew([
            'employee_id' => $data['employee_id'],
            'evaluation_period_id' => $data['evaluation_period_id'],
        ]);
        $eval->manager_score = $data['manager_score'] ?? $eval->manager_score;
        $eval->status ??= EvaluationStatus::Draft;
        $eval->save();

        $this->recalculate($eval);

        return back()->with('success', 'Evaluation scorecard saved.');
    }

    public function scoreEvaluation(Request $request, Evaluation $evaluation)
    {
        abort_unless($request->user()->can('score evaluations'), 403);
        $this->guardEmployeeInDept($request, (int) $evaluation->employee_id);
        abort_if($evaluation->period?->isLocked(), 403, 'This evaluation period is locked.');

        $data = $request->validate([
            'manager_score' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $evaluation->update(['manager_score' => $data['manager_score']]);
        $this->recalculate($evaluation);

        return back()->with('success', 'Manager score recorded.');
    }

    /**
     * Compute auto_score from system data for an evaluation in this dept.
     */
    public function computeAutoScore(Request $request, Evaluation $evaluation)
    {
        $this->guardEmployeeInDept($request, (int) $evaluation->employee_id);

        $calculator = app(\App\Services\AutoScoreCalculator::class);
        $result     = $calculator->computeAndSave($evaluation);

        $evaluation->load(['employee', 'period', 'gradeBand']);

        return response()->json([
            'message'       => 'Auto score computed from system data.',
            'evaluation_id' => $evaluation->id,
            'auto_score'    => $result['auto_score'],
            'final_score'   => $evaluation->final_score,
            'grade_band'    => $evaluation->gradeBand?->label_en,
            'breakdown'     => $result['breakdown'],
        ]);
    }

    // ===================== DELIVERABLES =====================

    public function storeDeliverable(Request $request)
    {
        abort_unless($request->user()->can('manage deliverables'), 403);

        $data = $request->validate([
            'fortnight_id' => ['required', 'exists:fortnights,id'],
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
        ]);

        $this->guardTeamUser($request, (int) $data['user_id']);
        Deliverable::create($data);

        return back()->with('success', 'Deliverable created.');
    }

    public function updateDeliverable(Request $request, Deliverable $deliverable)
    {
        abort_unless($request->user()->can('manage deliverables'), 403);
        $this->guardTeamUser($request, (int) $deliverable->user_id);

        $data = $request->validate([
            'fortnight_id' => ['required', 'exists:fortnights,id'],
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
        ]);

        $this->guardTeamUser($request, (int) $data['user_id']);
        $deliverable->update($data);

        return back()->with('success', 'Deliverable updated.');
    }

    public function toggleDeliverable(Request $request, Deliverable $deliverable)
    {
        abort_unless($request->user()->can('manage deliverables'), 403);
        $this->guardTeamUser($request, (int) $deliverable->user_id);

        $done = ! $deliverable->is_completed;
        $deliverable->update([
            'is_completed' => $done,
            'reviewed_by' => $done ? $request->user()->id : null,
        ]);

        return back()->with('success', $done ? 'Deliverable signed off.' : 'Deliverable reopened.');
    }

    public function destroyDeliverable(Request $request, Deliverable $deliverable)
    {
        abort_unless($request->user()->can('manage deliverables'), 403);
        $this->guardTeamUser($request, (int) $deliverable->user_id);

        $deliverable->delete();

        return back()->with('success', 'Deliverable removed.');
    }

    // ===================== SCORING ENGINE =====================

    /**
     * Multi-rater final score = 0.40 auto + 0.40 manager + 0.20 executive
     * (weights from settings). Assigns the matching grade band and raises an
     * increment recommendation when the band triggers one.
     */
    private function recalculate(Evaluation $eval): void
    {
        $w = $this->weights();
        $final = ((float) ($eval->auto_score ?? 0)) * $w['auto']
            + ((float) ($eval->manager_score ?? 0)) * $w['manager']
            + ((float) ($eval->executive_score ?? 0)) * $w['executive'];

        $band = GradeBand::where('min_score', '<=', $final)->where('max_score', '>=', $final)->first();

        $eval->update([
            'final_score' => $final,
            'grade_band_id' => $band?->id,
        ]);

        if ($band && $band->triggers_increment && $eval->employee && $eval->employee->base_salary > 0) {
            $proposed = (float) $eval->employee->base_salary * (1 + $band->increment_pct / 100);
            IncrementRecommendation::updateOrCreate(
                ['evaluation_id' => $eval->id],
                ['current_salary' => $eval->employee->base_salary, 'proposed_salary' => $proposed, 'status' => 'pending'],
            );
        } else {
            IncrementRecommendation::where('evaluation_id', $eval->id)->delete();
        }
    }

    /** @return array{auto:float, manager:float, executive:float} */
    private function weights(): array
    {
        return [
            'auto' => (float) Setting::get('weight_auto_score', 0.40),
            'manager' => (float) Setting::get('weight_manager_score', 0.40),
            'executive' => (float) Setting::get('weight_executive_score', 0.20),
        ];
    }

    // ===================== SCOPING GUARDS =====================

    /** @return array<int, int> */
    private function deptIds(Request $request): array
    {
        return $request->user()->managedDepartmentIds();
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function employeeIds(Request $request)
    {
        return Employee::whereIn('department_id', $this->deptIds($request))->pluck('id');
    }

    /** Team user accounts (employees in the managed departments, plus the head). */
    private function teamUserIds(Request $request)
    {
        return Employee::whereIn('department_id', $this->deptIds($request))
            ->whereNotNull('user_id')->pluck('user_id')
            ->push($request->user()->id)->unique()->values();
    }

    private function canManage(Request $request, ?int $deptId): bool
    {
        $user = $request->user();
        return $user->hasRole('President / Super Admin') || $user->managesDepartment($deptId);
    }

    /**
     * Departments this user *heads* (not merely belongs to) — the archive is
     * scoped tighter than task/KPI management: head + admin only.
     */
    private function headedDepartments(Request $request)
    {
        $user = $request->user();

        return $user->hasRole('President / Super Admin')
            ? Department::query()
            : $user->headedDepartments();
    }

    private function guardHeadsDepartment(Request $request, int $departmentId): void
    {
        $user = $request->user();
        $isHead = $user->hasRole('President / Super Admin')
            || $user->headedDepartments()->where('id', $departmentId)->exists();

        abort_unless($isHead, 403, 'You do not head that department.');
    }

    private function canManageTasks(Request $request): bool
    {
        return $request->user()->canAny(['manage department tasks', 'manage all tasks']);
    }

    private function guardEmployeeInDept(Request $request, int $employeeId): void
    {
        abort_unless($this->canManageTasks($request), 403);
        $employee = Employee::find($employeeId);
        abort_unless($employee && $this->canManage($request, $employee->department_id), 403, 'That employee is not in your department.');
    }

    private function guardTaskInDept(Request $request, Task $task): void
    {
        abort_unless($this->canManageTasks($request), 403);
        abort_unless($this->canManage($request, $task->employee?->department_id), 403);
    }

    private function guardKpiInDept(Request $request, Kpi $kpi): void
    {
        $ids = $this->employeeIds($request);
        $assigned = $kpi->employees()->whereIn('employees.id', $ids)->exists();
        abort_unless($assigned || $request->user()->hasRole('President / Super Admin'), 403, 'That KPI is not assigned to your team.');
    }

    private function guardTeamUser(Request $request, int $userId): void
    {
        abort_unless($this->teamUserIds($request)->contains($userId), 403, 'That person is not on your team.');
    }

    private function guardPeriodOpen(int $periodId): void
    {
        $period = EvaluationPeriod::find($periodId);
        abort_if($period?->isLocked(), 403, 'This evaluation period is locked.');
    }

    // ===================== HELPERS =====================

    /** @return array<string, mixed> */
    private function validateTask(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'target_id' => ['nullable', 'exists:targets,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cadence' => ['required', Rule::in(array_map(fn ($c) => $c->value, Cadence::cases()))],
            'starting_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:starting_date'],
            'weight' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_map(fn ($s) => $s->value, TaskStatus::cases()))],
            'completion_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    /** @return array<int, array{value:string, label:string}> */
    private function options(array $cases): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);
    }
}
