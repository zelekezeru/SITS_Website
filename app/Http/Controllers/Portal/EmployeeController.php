<?php

namespace App\Http\Controllers\Portal;

use App\Enums\Cadence;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Payslip;
use App\Models\Target;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Employee self-service portal. Every query is scoped to the signed-in user's
 * own employee record — an employee only ever sees and edits their own work.
 * Mutations are gated by the "manage own tasks" permission and a hard owner
 * check, so the coarse route has fine-grained teeth.
 */
class EmployeeController extends Controller
{
    /** Statuses an employee may set on their own tasks (system sets "missed"). */
    private const SELF_STATUSES = [
        TaskStatus::Pending,
        TaskStatus::InProgress,
        TaskStatus::Submitted,
        TaskStatus::Completed,
    ];

    // ===================== PAGES =====================

    public function tasks(Request $request): Response
    {
        $employee = $request->user()->employee;

        $tasks = $employee
            ? $this->orderTasks(
                Task::where('employee_id', $employee->id)->with(['target:id,name', 'kpis:id,title_en'])->get()
            )
            : collect();

        return Inertia::render('Employee/Tasks', [
            'tasks' => $tasks,
            'targets' => Target::orderBy('name')->get(['id', 'name']),
            'fortnight' => $this->fortnightPayload(),
            'cadences' => $this->options(Cadence::cases()),
            'statuses' => collect(self::SELF_STATUSES)->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'can' => [
                'create' => (bool) $employee && $request->user()->canAny(['manage own tasks', 'create tasks', 'manage all tasks']),
            ],
        ]);
    }

    public function kpis(Request $request): Response
    {
        $employee = $request->user()->employee;

        $kpis = $employee
            ? $employee->kpis()->with('kpiable')->get()
            : collect();

        return Inertia::render('Employee/Kpis', [
            'kpis' => $kpis,
        ]);
    }

    public function evaluations(Request $request): Response
    {
        $employee = $request->user()->employee;

        $evaluations = $employee
            ? Evaluation::where('employee_id', $employee->id)
                ->with(['period', 'gradeBand', 'ratings.kpi:id,title_en', 'ratings.rater:id,name'])
                ->latest()
                ->get()
            : collect();

        return Inertia::render('Employee/Evaluations', [
            'evaluations' => $evaluations,
        ]);
    }

    public function payslips(Request $request): Response
    {
        $employee = $request->user()->employee;

        $payslips = $employee
            ? Payslip::where('employee_id', $employee->id)
                ->with(['payrollPeriod', 'lines'])
                ->latest()
                ->get()
            : collect();

        return Inertia::render('Employee/Payslips', [
            'payslips' => $payslips,
        ]);
    }

    public function payslipPdf(Request $request, Payslip $payslip)
    {
        abort_unless($payslip->employee_id == $request->user()->employee?->id, 403);

        $payslip->load(['employee.position', 'employee.department', 'payrollPeriod', 'lines']);

        $pdf = Pdf::loadView('pdf.payslip', ['payslip' => $payslip])->setPaper('a4');
        $name = str_replace(' ', '_', $payslip->employee?->staff_no ?? 'payslip');

        return $pdf->download("payslip_{$name}_{$payslip->id}.pdf");
    }

    public function profile(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee?->load(['position', 'department.campus', 'reportingTo']);

        return Inertia::render('Employee/Profile', [
            'employee' => $employee,
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'passwordChanged' => (bool) $user->password_changed,
            ],
        ]);
    }

    // ===================== TASK MUTATIONS =====================

    public function storeTask(Request $request)
    {
        $employee = $this->guardEmployee($request);

        $data = $this->validateTask($request);
        $data['employee_id'] = $employee->id;
        $data['created_by'] = $request->user()->id;
        $data['assigned_by_id'] = $request->user()->id;

        Task::create($data);

        return back()->with('success', 'Task added to your plan.');
    }

    public function updateTask(Request $request, Task $task)
    {
        $this->guardOwnTask($request, $task);

        $task->update($this->validateTask($request));

        return back()->with('success', 'Task updated.');
    }

    /** Lightweight inline update for the progress slider / status chip. */
    public function updateProgress(Request $request, Task $task)
    {
        $this->guardOwnTask($request, $task);

        $data = $request->validate([
            'status' => ['required', $this->statusRule()],
            'completion_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $task->update($data);

        return back()->with('success', 'Progress saved.');
    }

    public function destroyTask(Request $request, Task $task)
    {
        $this->guardOwnTask($request, $task);

        $task->delete();

        return back()->with('success', 'Task removed.');
    }

    // ===================== ACCOUNT =====================

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
            'password_changed' => true,
            'default_password' => null,
        ]);

        return back()->with('success', 'Password updated.');
    }

    // ===================== HELPERS =====================

    private function guardEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;
        abort_unless($employee !== null, 403, 'No employee profile is linked to your account.');
        abort_unless($request->user()->canAny(['manage own tasks', 'create tasks', 'manage all tasks']), 403);

        return $employee;
    }

    private function guardOwnTask(Request $request, Task $task): void
    {
        $user = $request->user();
        $owns = $task->employee?->user_id == $user->id;
        abort_unless($owns || $user->can('manage all tasks'), 403);
    }

    /** @return array<string, mixed> */
    private function validateTask(Request $request): array
    {
        return $request->validate([
            'target_id' => ['nullable', 'exists:targets,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cadence' => ['required', $this->enumRule(Cadence::cases())],
            'starting_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:starting_date'],
            'weight' => ['required', 'numeric', 'min:0'],
            'status' => ['required', $this->statusRule()],
            'completion_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function statusRule(): \Illuminate\Validation\Rules\In
    {
        return \Illuminate\Validation\Rule::in(array_map(fn ($s) => $s->value, self::SELF_STATUSES));
    }

    private function enumRule(array $cases): \Illuminate\Validation\Rules\In
    {
        return \Illuminate\Validation\Rule::in(array_map(fn ($c) => $c->value, $cases));
    }

    /** @return array<int, array{value:string, label:string}> */
    private function options(array $cases): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);
    }

    /**
     * Active work first, then by due date — sorted in PHP so the query stays
     * portable across MySQL (prod) and SQLite (tests).
     *
     * @param  \Illuminate\Support\Collection<int, Task>  $tasks
     * @return \Illuminate\Support\Collection<int, Task>
     */
    private function orderTasks($tasks)
    {
        $order = ['in_progress' => 0, 'pending' => 1, 'submitted' => 2, 'completed' => 3, 'missed' => 4];

        return $tasks
            ->sortBy(fn (Task $t) => $t->due_date?->getTimestamp() ?? PHP_INT_MAX)
            ->sortBy(fn (Task $t) => $order[$t->status->value] ?? 9)
            ->values();
    }

    /** @return array<string, mixed>|null */
    private function fortnightPayload(): ?array
    {
        $fortnight = \App\Models\Fortnight::current();
        if (! $fortnight) {
            return null;
        }

        return [
            'name' => $fortnight->name,
            'start_date' => $fortnight->start_date?->toDateString(),
            'end_date' => $fortnight->end_date?->toDateString(),
        ];
    }
}
