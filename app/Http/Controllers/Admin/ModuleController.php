<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Goal;
use App\Models\Strategy;
use App\Models\Target;
use App\Models\Year;
use App\Models\Campus;
use App\Models\Position;
use App\Models\Employee;
use App\Models\JobDescription;
use App\Models\JobDescriptionVersion;
use App\Models\Quarter;
use App\Models\Fortnight;
use App\Models\Day;
use App\Models\User;
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
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\AttendanceRecord;
use App\Models\AttendanceImport;
use App\Models\TaxBracket;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Setting;
use App\Enums\ArchiveResourceType;
use App\Enums\StrategicPillar;
use App\Enums\EmploymentType;
use App\Support\AdminNavigation;
use App\Support\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the landing page for any admin module. The module's metadata is
 * resolved from the current route name against the single navigation tree, so
 * every menu entry has a real, access-controlled page behind it.
 *
 * For customized views, it fetches the required database metrics and renders
 * specialized page components.
 */
class ModuleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $routeName = $request->route()->getName();
        $module = AdminNavigation::module($routeName);

        abort_if($module === null, 404);

        // ==========================================
        // STRATEGY MODULE
        // ==========================================
        if ($routeName === 'admin.strategy') {
            $activeYear = FiscalYear::current();
            $strategies = $activeYear 
                ? Strategy::where('year_id', $activeYear->id)->with('goals.targets.departments')->get()
                : [];
            
            return Inertia::render('Admin/Strategy/Index', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'activeYear' => $activeYear,
                'strategies' => $strategies,
                'pillars' => collect(StrategicPillar::cases())->map(fn($p) => [
                    'value' => $p->value,
                    'label' => $p->label(),
                ]),
            ]);
        }

        if ($routeName === 'admin.strategy.years') {
            return Inertia::render('Admin/Strategy/Years', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'years' => Year::orderBy('start_date', 'desc')->get(),
            ]);
        }

        if ($routeName === 'admin.strategy.strategies') {
            $activeYear = FiscalYear::current();
            $strategies = $activeYear
                ? Strategy::where('year_id', $activeYear->id)->with('year')->get()
                : [];

            return Inertia::render('Admin/Strategy/Strategies', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'strategies' => $strategies,
                'years' => Year::all(),
                'pillars' => collect(StrategicPillar::cases())->map(fn($p) => [
                    'value' => $p->value,
                    'label' => $p->label(),
                ]),
            ]);
        }

        if ($routeName === 'admin.strategy.goals') {
            $activeYear = FiscalYear::current();
            $strategies = $activeYear
                ? Strategy::where('year_id', $activeYear->id)->get()
                : Strategy::all();

            $goals = Goal::whereIn('strategy_id', $strategies->pluck('id'))->with('strategy')->get();

            return Inertia::render('Admin/Strategy/Goals', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'goals' => $goals,
                'strategies' => $strategies,
            ]);
        }

        if ($routeName === 'admin.strategy.targets') {
            $activeYear = FiscalYear::current();
            $strategies = $activeYear
                ? Strategy::where('year_id', $activeYear->id)->get()
                : Strategy::all();
            
            $goals = Goal::whereIn('strategy_id', $strategies->pluck('id'))->get();
            $targets = Target::whereIn('goal_id', $goals->pluck('id'))->with(['goal', 'departments'])->get();

            return Inertia::render('Admin/Strategy/Targets', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'targets' => $targets,
                'goals' => $goals,
                'departments' => Department::all(['id', 'name_en']),
                'years' => Year::all(),
            ]);
        }

        // ==========================================
        // CALENDAR MODULE
        // ==========================================
        if ($routeName === 'admin.calendar.quarters') {
            $activeYear = FiscalYear::current();
            $quarters = $activeYear 
                ? Quarter::where('year_id', $activeYear->id)->with('fortnights')->get()
                : [];

            return Inertia::render('Admin/Calendar/Quarters', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'quarters' => $quarters,
                'years' => Year::all(),
                'activeYear' => $activeYear,
            ]);
        }

        if ($routeName === 'admin.calendar.fortnights') {
            $activeYear = FiscalYear::current();
            $quarters = $activeYear 
                ? Quarter::where('year_id', $activeYear->id)->get()
                : Quarter::all();

            $fortnights = Fortnight::whereIn('quarter_id', $quarters->pluck('id'))->with(['quarter', 'days'])->get();

            return Inertia::render('Admin/Calendar/Fortnights', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'fortnights' => $fortnights,
                'quarters' => $quarters,
                'activeYear' => $activeYear,
            ]);
        }

        if ($routeName === 'admin.calendar.days') {
            $activeYear = FiscalYear::current();
            $quarters = $activeYear 
                ? Quarter::where('year_id', $activeYear->id)->get()
                : Quarter::all();
            
            $fortnights = Fortnight::whereIn('quarter_id', $quarters->pluck('id'))->get();
            $days = Day::whereIn('fortnight_id', $fortnights->pluck('id'))->orWhereNull('fortnight_id')->with('fortnight')->orderBy('date', 'desc')->get();

            return Inertia::render('Admin/Calendar/Days', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'days' => $days,
                'fortnights' => $fortnights,
            ]);
        }

        // ==========================================
        // PEOPLE MODULE
        // ==========================================
        if ($routeName === 'admin.employees') {
            $employees = Employee::with(['position', 'department', 'user', 'reportingTo'])
                ->orderBy('full_name_en')->get();

            $users = User::with('roles')->orderBy('name')->get();

            return Inertia::render('Admin/People/Employees', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'employees' => $employees,
                'positions' => Position::all(),
                'departments' => Department::all(),
                'users' => $users,
                'employmentTypes' => collect(EmploymentType::cases())->map(fn($e) => [
                    'value' => $e->value,
                    'label' => $e->label(),
                ]),
            ]);
        }

        if ($routeName === 'admin.job-descriptions') {
            return Inertia::render('Admin/People/JobDescriptions', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'jobDescriptions' => JobDescription::with(['position', 'currentVersion', 'versions.createdBy'])->get(),
                'positions' => Position::all(),
            ]);
        }

        if ($routeName === 'admin.organization.campuses') {
            return Inertia::render('Admin/People/Campuses', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'campuses' => Campus::all(),
            ]);
        }

        if ($routeName === 'admin.organization.departments') {
            return Inertia::render('Admin/People/Departments', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'departments' => Department::with(['parent', 'campus', 'head'])->get(),
                'campuses' => Campus::all(),
                'users' => User::all(),
            ]);
        }

        if ($routeName === 'admin.organization.positions') {
            return Inertia::render('Admin/People/Positions', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'positions' => Position::all(),
            ]);
        }

        // ==========================================
        // PERFORMANCE MODULE
        // ==========================================
        if (in_array($routeName, ['admin.kpis', 'admin.kpis.index', 'admin.kpis.approvals', 'admin.kpis.confirmations'])) {
            return Inertia::render('Admin/Performance/Kpis', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'kpis' => Kpi::with(['employees', 'approvedBy', 'confirmedBy', 'createdBy', 'kpiable.jobDescription'])->get(),
                'employees' => Employee::all(),
                'targets' => Target::all(),
                'jobDescriptionVersions' => JobDescriptionVersion::with('jobDescription')->get(),
            ]);
        }

        if (in_array($routeName, ['admin.tasks', 'admin.tasks.index', 'admin.tasks.fortnights', 'admin.tasks.progress'])) {
            return Inertia::render('Admin/Performance/Tasks', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'tasks' => Task::with(['employee', 'target'])->get(),
                'employees' => Employee::all(),
                'targets' => Target::all(),
                'fortnights' => Fortnight::all(),
            ]);
        }

        if ($routeName === 'admin.deliverables') {
            return Inertia::render('Admin/Performance/Deliverables', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'deliverables' => Deliverable::with(['fortnight', 'user', 'reviewedBy'])->get(),
                'fortnights' => Fortnight::all(),
                'users' => User::all(),
            ]);
        }
        if (in_array($routeName, ['admin.evaluations', 'admin.evaluations.index', 'admin.evaluations.periods', 'admin.evaluations.ratings'])) {
            return Inertia::render('Admin/Performance/Evaluations', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'periods' => EvaluationPeriod::all(),
                'evaluations' => Evaluation::with(['employee', 'period', 'gradeBand'])->get(),
                'ratings' => EvaluationRating::with(['evaluation.employee', 'kpi', 'rater'])->get(),
                'employees' => Employee::all(),
                'kpis' => Kpi::all(),
                'users' => User::all(),
                'years' => Year::orderBy('label', 'desc')->get(),
                'fortnights' => Fortnight::all(),
            ]);
        }

        if (in_array($routeName, ['admin.ai-analysis', 'admin.ai-analysis.reports', 'admin.ai-analysis.insights'])) {
            $defaultProvider = Setting::get('ai_default_provider', config('ai.default', 'claude_pro'));
            return Inertia::render('Admin/Performance/AiAnalysis', [
                'module'      => $module,
                'nav'         => AdminNavigation::sections(),
                'routeName'   => $routeName,
                'reports'     => NarrativeReport::with(['employee', 'period'])->latest()->get(),
                'analyses'    => AiAnalysis::with(['narrativeReport.employee', 'confirmedBy'])->latest()->get(),
                'evaluations' => Evaluation::with(['employee', 'period'])->latest()->get(),
                'employees'   => Employee::all(['id', 'full_name_en', 'full_name_am']),
                'periods'     => EvaluationPeriod::all(['id', 'name']),
                'aiProvider'  => $defaultProvider,
                'aiModel'     => Setting::get("{$defaultProvider}_model", config("ai.providers.{$defaultProvider}.model")),
                'aiEnabled'   => (bool) Setting::get('ai_enabled', config('ai.enabled', false)),
            ]);
        }

        if (in_array($routeName, ['admin.grading', 'admin.grading.scales', 'admin.grading.increments'])) {
            return Inertia::render('Admin/Performance/Grading', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'scales' => GradeScale::with('bands')->get(),
                'increments' => IncrementRecommendation::with(['evaluation.employee', 'approvedBy'])->get(),
            ]);
        }

        // ==========================================
        // FINANCE MODULE (delegated to its own controller)
        // ==========================================
        if ($financePage = app(FinanceModuleController::class)->render($request, $routeName, $module)) {
            return $financePage;
        }

        // ==========================================
        // INSIGHTS MODULE
        // ==========================================
        if (in_array($routeName, ['admin.reports', 'admin.reports.executive', 'admin.reports.department', 'admin.reports.exports'])) {
            $activeYear = FiscalYear::current();
            $totalBudget = $activeYear ? Target::where('year_id', $activeYear->id)->sum('budget') : 0;
            $avgScore = Evaluation::avg('final_score') ?? 0;
            
            return Inertia::render('Admin/Insights/Reports', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'totalEmployees' => Employee::where('is_active', true)->count(),
                'totalBudget' => $totalBudget,
                'avgScore' => $avgScore,
                'employees' => Employee::with('department')->get(),
                'departments' => Department::with('campus')->get(),
            ]);
        }

        if ($routeName === 'admin.documents') {
            $docs = Document::with('uploadedBy')->get()->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->title,
                    'file_path' => $doc->path,
                    'documentable_type' => $doc->documentable_type,
                    'documentable_id' => $doc->documentable_id,
                    'uploader' => $doc->uploadedBy,
                ];
            });

            return Inertia::render('Admin/Insights/Documents', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'documents' => $docs,
                'employees' => Employee::all(),
            ]);
        }

        if ($routeName === 'admin.organization.archive') {
            // The institution archive hangs off a singleton row — ensure it exists
            // rather than 404'ing the page when it hasn't been seeded yet.
            $organization = Organization::firstOrCreate(
                ['id' => 1],
                ['name' => 'Shiloh International Theological Seminary']
            )->load('documents.uploadedBy');

            return Inertia::render('Admin/Administration/OrganizationArchive', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'organization' => $organization,
                'categories' => collect(ArchiveResourceType::cases())->map(fn ($c) => [
                    'value' => $c->value,
                    'label' => $c->label(),
                ]),
            ]);
        }

        if ($routeName === 'admin.audit') {
            $logs = DB::table('activity_log')
                ->orderBy('created_at', 'desc')
                ->take(100)
                ->get()
                ->map(function ($log) {
                    $user = null;
                    if ($log->causer_id && $log->causer_type === 'App\Models\User') {
                        $user = User::find($log->causer_id);
                    }
                    return [
                        'id' => $log->id,
                        'description' => $log->description,
                        'causer' => $user ? $user->name : 'System',
                        'created_at' => $log->created_at,
                    ];
                });

            return Inertia::render('Admin/Insights/AuditLog', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'logs' => $logs,
            ]);
        }

        // ==========================================
        // ADMINISTRATION MODULE
        // ==========================================
        if (in_array($routeName, ['admin.users', 'admin.users.index', 'admin.users.approvals', 'admin.users.roles', 'admin.users.deactivations'])) {
            $users = User::with('roles')->orderBy('name')->get();
            $deactivationRequests = \App\Models\DeactivationRequest::with('user')->latest()->get();

            return Inertia::render('Admin/Administration/UsersAccess', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'users' => $users,
                'roles' => Role::with('permissions')->get(),
                'permissions' => \Spatie\Permission\Models\Permission::all(),
                'deactivationRequests' => $deactivationRequests,
            ]);
        }

        if (in_array($routeName, ['admin.settings', 'admin.settings.general', 'admin.settings.scoring', 'admin.settings.localization', 'admin.settings.ai'])) {
            return Inertia::render('Admin/Administration/Settings', [
                'module' => $module,
                'nav' => AdminNavigation::sections(),
                'routeName' => $routeName,
                'settings' => Setting::all(),
            ]);
        }

        // Default fallback
        return Inertia::render('Admin/Module', [
            'module' => $module,
            'nav' => AdminNavigation::sections(),
        ]);
    }
}


