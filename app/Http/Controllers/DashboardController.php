<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationStatus;
use App\Enums\IncrementStatus;
use App\Enums\TaskStatus;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\IncrementRecommendation;
use App\Models\Fortnight;
use App\Models\Kpi;
use App\Models\Payslip;
use App\Models\Task;
use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\RoleLanding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Static metadata per landing. The route key (not the user's role) drives
     * the title/scope so each URL renders consistently; the account panel still
     * reflects the user's real roles.
     *
     * @var array<string, array{title:string, subtitle:string, accent:string, scope:string}>
     */
    private const PANELS = [
        'President / Super Admin' => [
            'title' => 'Executive Command Center',
            'subtitle' => 'Full oversight across every SITS ERP module.',
            'accent' => 'blue',
            'scope' => 'global',
        ],
        'Vice President' => [
            'title' => 'Executive Dashboard',
            'subtitle' => 'Institution-wide performance, approvals and payroll.',
            'accent' => 'indigo',
            'scope' => 'global',
        ],
        'Dean of the Seminary' => [
            'title' => 'Academic Leadership',
            'subtitle' => 'Oversee academic departments, KPIs and evaluations.',
            'accent' => 'violet',
            'scope' => 'department',
        ],
        'Operational Manager' => [
            'title' => 'Operations Center',
            'subtitle' => 'Attendance, KPIs and departmental delivery.',
            'accent' => 'cyan',
            'scope' => 'department',
        ],
        'Finance Officer' => [
            'title' => 'Finance Office',
            'subtitle' => 'Payroll preparation, deductions and approved exports.',
            'accent' => 'teal',
            'scope' => 'finance',
        ],
        'Department Head' => [
            'title' => 'Department Head Workspace',
            'subtitle' => 'Your team, their tasks and KPI approvals.',
            'accent' => 'emerald',
            'scope' => 'department',
        ],
        'Registrar' => [
            'title' => 'Registrar Office',
            'subtitle' => 'Staff records and institutional reporting.',
            'accent' => 'amber',
            'scope' => 'records',
        ],
        'Employee' => [
            'title' => 'My Workspace',
            'subtitle' => 'Your tasks, KPIs, evaluations and payslips.',
            'accent' => 'slate',
            'scope' => 'self',
        ],
    ];

    public function admin(Request $request): Response
    {
        // The President gets the full sidebar shell + executive command center.
        return $this->render($request, 'President / Super Admin', 'Admin/Dashboard', [
            'nav' => AdminNavigation::sections(),
        ]);
    }

    public function executive(Request $request): Response
    {
        return $this->render($request, 'Vice President');
    }

    public function dean(Request $request): Response
    {
        return $this->render($request, 'Dean of the Seminary');
    }

    public function operations(Request $request): Response
    {
        return $this->render($request, 'Operational Manager');
    }

    public function department(Request $request): Response
    {
        return $this->render($request, 'Department Head', 'Dashboard', [
            'portalData' => $this->departmentData($request->user()),
        ]);
    }

    public function registrar(Request $request): Response
    {
        return $this->render($request, 'Registrar');
    }

    public function employee(Request $request): Response
    {
        return $this->render($request, 'Employee', 'Dashboard', [
            'portalData' => $this->employeeData($request->user()),
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function render(Request $request, string $roleKey, string $component = 'Dashboard', array $extra = []): Response
    {
        $user = $request->user();
        $meta = self::PANELS[$roleKey];

        return Inertia::render($component, [
            'panel' => [
                'roleLabel' => $roleKey,
                'resolvedRole' => RoleLanding::resolvedRole($user),
                'title' => $meta['title'],
                'subtitle' => $meta['subtitle'],
                'accent' => $meta['accent'],
                'scope' => $meta['scope'],
                'stats' => $this->statsFor($user, $meta['scope']),
                'capabilities' => $this->capabilities($user),
                'account' => $this->account($user),
            ],
            ...$extra,
        ]);
    }

    /**
     * Real metrics, scoped to what this role is allowed to see.
     *
     * @return array<int, array{label:string, value:int|string, hint:string}>
     */
    private function statsFor(User $user, string $scope): array
    {
        return match ($scope) {
            'global' => $this->globalStats(),
            'department' => $this->departmentStats($user),
            'records' => $this->recordsStats(),
            default => $this->selfStats($user),
        };
    }

    /** @return array<int, array{label:string, value:int|string, hint:string}> */
    private function globalStats(): array
    {
        return [
            $this->stat('Active Employees', Employee::where('is_active', true)->count(), 'Across all departments'),
            $this->stat('Open Tasks', Task::whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])->count(), 'Pending or in progress'),
            $this->stat('Confirmed KPIs', Kpi::confirmed()->count(), 'Counting toward scores'),
            $this->stat('Pending Increments', IncrementRecommendation::where('status', IncrementStatus::Pending)->count(), 'Awaiting approval'),
        ];
    }

    /** @return array<int, array{label:string, value:int|string, hint:string}> */
    private function departmentStats(User $user): array
    {
        $deptIds = $user->managedDepartmentIds();
        $employeeIds = Employee::whereIn('department_id', $deptIds)->pluck('id');

        $deptCount = count($deptIds);
        $hint = $deptCount === 1 ? 'In your department' : "Across {$deptCount} departments";

        return [
            $this->stat('Team Members', $employeeIds->count(), $hint),
            $this->stat('Open Team Tasks', Task::whereIn('employee_id', $employeeIds)
                ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])->count(), 'Pending or in progress'),
            $this->stat('Evaluations to Score', Evaluation::whereIn('employee_id', $employeeIds)
                ->where('status', EvaluationStatus::Draft)->count(), 'Drafts awaiting your input'),
            $this->stat('Assigned KPIs', Kpi::confirmed()
                ->whereHas('employees', fn ($q) => $q->whereIn('employees.id', $employeeIds))->count(), 'Confirmed & assigned'),
        ];
    }

    /** @return array<int, array{label:string, value:int|string, hint:string}> */
    private function recordsStats(): array
    {
        return [
            $this->stat('Total Staff', Employee::count(), 'All records'),
            $this->stat('Active Staff', Employee::where('is_active', true)->count(), 'Currently employed'),
            $this->stat('Departments', Department::count(), 'Organizational units'),
            $this->stat('Campuses', Campus::count(), 'Locations'),
        ];
    }

    /** @return array<int, array{label:string, value:int|string, hint:string}> */
    private function selfStats(User $user): array
    {
        $employee = $user->employee;

        if (! $employee) {
            return [
                $this->stat('Profile', 'Pending', 'No employee record linked yet'),
            ];
        }

        $latestPayslip = Payslip::where('employee_id', $employee->id)->latest()->first();

        return [
            $this->stat('My Open Tasks', Task::where('employee_id', $employee->id)
                ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])->count(), 'To do this period'),
            $this->stat('My KPIs', $employee->kpis()->count(), 'Assigned to you'),
            $this->stat('My Evaluations', Evaluation::where('employee_id', $employee->id)->count(), 'On record'),
            $this->stat('Latest Net Pay', $latestPayslip ? 'ETB '.number_format((float) $latestPayslip->net_pay, 2) : '—', 'Most recent payslip'),
        ];
    }

    /** @return array{label:string, value:int|string, hint:string} */
    private function stat(string $label, int|string $value, string $hint): array
    {
        return ['label' => $label, 'value' => $value, 'hint' => $hint];
    }

    /**
     * Rich self-service payload for the Employee dashboard: their upcoming
     * work, KPI snapshot, latest evaluation and most recent payslip.
     *
     * @return array<string, mixed>|null
     */
    private function employeeData(User $user): ?array
    {
        $employee = $user->employee;
        if (! $employee) {
            return null;
        }

        $tasks = Task::where('employee_id', $employee->id)->with('target:id,name')->get();
        $open = $tasks->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress]);

        $latestEval = Evaluation::where('employee_id', $employee->id)
            ->with(['period:id,name', 'gradeBand:id,label_en'])->latest()->first();
        $latestPayslip = Payslip::where('employee_id', $employee->id)
            ->with('payrollPeriod:id,name')->latest()->first();

        return [
            'fortnight' => $this->fortnightPayload(),
            'taskBreakdown' => [
                'open' => $open->count(),
                'completed' => $tasks->where('status', TaskStatus::Completed)->count(),
                'total' => $tasks->count(),
            ],
            'upcomingTasks' => $open->sortBy('due_date')->take(5)->values()->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'due_date' => $t->due_date?->toDateString(),
                'status' => $t->status->value,
                'completion_pct' => (float) $t->completion_pct,
                'target' => $t->target?->name,
            ]),
            'kpis' => $employee->kpis()->get()->map(fn (Kpi $k) => [
                'id' => $k->id,
                'title' => $k->title_en,
                'status' => $k->status->value,
                'confirmed' => $k->isConfirmed(),
                'weight' => (float) $k->weight,
            ]),
            'latestEvaluation' => $latestEval ? [
                'period' => $latestEval->period?->name,
                'final_score' => $latestEval->final_score !== null ? round((float) $latestEval->final_score, 1) : null,
                'grade' => $latestEval->gradeBand?->label_en,
                'status' => $latestEval->status->value,
            ] : null,
            'latestPayslip' => $latestPayslip ? [
                'period' => $latestPayslip->payrollPeriod?->name,
                'net_pay' => (float) $latestPayslip->net_pay,
                'status' => $latestPayslip->status->value,
            ] : null,
        ];
    }

    /**
     * Rich payload for the Department Head dashboard: pending approvals, drafts
     * awaiting their score and the team's most pressing open tasks.
     *
     * @return array<string, mixed>
     */
    private function departmentData(User $user): array
    {
        $deptIds = $user->managedDepartmentIds();
        $employeeIds = Employee::whereIn('department_id', $deptIds)->pluck('id');

        return [
            'fortnight' => $this->fortnightPayload(),
            'pendingKpis' => Kpi::whereNull('approved_by')
                ->whereHas('employees', fn ($q) => $q->whereIn('employees.id', $employeeIds))
                ->with('employees:id,full_name_en')->latest()->take(5)->get()
                ->map(fn (Kpi $k) => [
                    'id' => $k->id,
                    'title' => $k->title_en,
                    'employees' => $k->employees->pluck('full_name_en')->take(2)->implode(', '),
                ]),
            'evaluationsToScore' => Evaluation::whereIn('employee_id', $employeeIds)
                ->where('status', EvaluationStatus::Draft)
                ->with(['employee:id,full_name_en', 'period:id,name'])->take(5)->get()
                ->map(fn (Evaluation $e) => [
                    'id' => $e->id,
                    'employee' => $e->employee?->full_name_en,
                    'period' => $e->period?->name,
                ]),
            'openTasks' => Task::whereIn('employee_id', $employeeIds)
                ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])
                ->with('employee:id,full_name_en')->orderBy('due_date')->take(6)->get()
                ->map(fn (Task $t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'employee' => $t->employee?->full_name_en,
                    'due_date' => $t->due_date?->toDateString(),
                    'completion_pct' => (float) $t->completion_pct,
                    'status' => $t->status->value,
                ]),
        ];
    }

    /** @return array<string, mixed>|null */
    private function fortnightPayload(): ?array
    {
        $fortnight = Fortnight::current();
        if (! $fortnight) {
            return null;
        }

        return [
            'name' => $fortnight->name,
            'start_date' => $fortnight->start_date?->toDateString(),
            'end_date' => $fortnight->end_date?->toDateString(),
        ];
    }

    /** Humanized list of the user's effective permissions (proves RBAC wiring). */
    private function capabilities(User $user): array
    {
        return $user->getAllPermissions()
            ->pluck('name')
            ->map(fn (string $name) => ucfirst($name))
            ->sort()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function account(User $user): array
    {
        $employee = $user->employee;

        return [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->all(),
            'staffNo' => $employee?->staff_no,
            'department' => $employee?->department?->name_en,
            'employmentType' => $employee?->employment_type?->label(),
            'passwordChanged' => (bool) $user->password_changed,
        ];
    }
}
