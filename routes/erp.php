<?php

/*
|--------------------------------------------------------------------------
| SITS ERP (Performance Management System) routes
|--------------------------------------------------------------------------
| Merged from the standalone PMS app. These are Inertia/Vue pages that live
| alongside the public Blade website. Authentication is handled by the
| website's Breeze login; after login users are forwarded to their
| role-based landing (see App\Support\RoleLanding). This file is required
| from routes/web.php inside the "web" middleware group.
*/

use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\StrategyCrudController;
use App\Http\Controllers\Admin\CalendarCrudController;
use App\Http\Controllers\Admin\PeopleCrudController;
use App\Http\Controllers\Admin\PerformanceCrudController;
use App\Http\Controllers\Admin\FinanceCrudController;
use App\Http\Controllers\Admin\AttendanceImportController;
use App\Http\Controllers\Admin\AdminCrudController;
use App\Http\Controllers\Admin\ConductController;
use App\Http\Controllers\Admin\EmployeeLifecycleController;
use App\Http\Controllers\Portal\EmployeeController;
use App\Http\Controllers\Portal\DepartmentController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\PayrollController as FinancePayrollController;
use App\Http\Controllers\Admin\PayrollConfigController;
use App\Http\Controllers\AttendancePermissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Support\AdminNavigation;
use App\Support\RoleLanding;
use Illuminate\Support\Facades\Route;

// Switch the UI language (11 supported languages); persisted in the session and a long-lived cookie.
Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $supported = ['en', 'am', 'om', 'ti', 'so', 'sw', 'zh', 'fr', 'es', 'ku', 'ur'];
    $request->validate(['locale' => ['required', 'in:' . implode(',', $supported)]]);

    $locale = $request->string('locale')->toString();
    $request->session()->put('locale', $locale);

    return back()->withCookie(cookie()->forever('locale', $locale));
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Authenticated + active account (ERP)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'password.fresh'])->group(function () {
    // Forced first-login / post-recovery password change.
    Route::get('/password/force-change', [\App\Http\Controllers\Auth\ForcePasswordController::class, 'showForceChange'])->name('password.force-change');
    Route::post('/password/force-change', [\App\Http\Controllers\Auth\ForcePasswordController::class, 'forceChange'])->name('password.force-change.update');

    // ---- President / Super Admin: full module navigation ------------------
    Route::middleware('role.landing:President / Super Admin')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');

        $seen = [];
        foreach (AdminNavigation::modules() as $module) {
            if (in_array($module['path'], $seen, true)) {
                continue; // a child that shares its parent's path (e.g. an "index" alias)
            }
            $seen[] = $module['path'];

            Route::get($module['path'], ModuleController::class)->name($module['name']);
        }

        // Strategy CRUD routes
        Route::post('/admin/strategy/years', [StrategyCrudController::class, 'storeYear'])->name('admin.strategy.years.store');
        Route::get('/admin/strategy/years/{year}', [StrategyCrudController::class, 'showYear'])->name('admin.strategy.years.show');
        Route::put('/admin/strategy/years/{year}', [StrategyCrudController::class, 'updateYear'])->name('admin.strategy.years.update');
        Route::delete('/admin/strategy/years/{year}', [StrategyCrudController::class, 'destroyYear'])->name('admin.strategy.years.destroy');
        Route::post('/admin/strategy/years/{year}/activate', [StrategyCrudController::class, 'activateYear'])->name('admin.strategy.years.activate');

        Route::post('/admin/strategy/strategies', [StrategyCrudController::class, 'storeStrategy'])->name('admin.strategy.strategies.store');
        Route::put('/admin/strategy/strategies/{strategy}', [StrategyCrudController::class, 'updateStrategy'])->name('admin.strategy.strategies.update');
        Route::delete('/admin/strategy/strategies/{strategy}', [StrategyCrudController::class, 'destroyStrategy'])->name('admin.strategy.strategies.destroy');

        Route::post('/admin/strategy/goals', [StrategyCrudController::class, 'storeGoal'])->name('admin.strategy.goals.store');
        Route::put('/admin/strategy/goals/{goal}', [StrategyCrudController::class, 'updateGoal'])->name('admin.strategy.goals.update');
        Route::delete('/admin/strategy/goals/{goal}', [StrategyCrudController::class, 'destroyGoal'])->name('admin.strategy.goals.destroy');

        Route::post('/admin/strategy/targets', [StrategyCrudController::class, 'storeTarget'])->name('admin.strategy.targets.store');
        Route::put('/admin/strategy/targets/{target}', [StrategyCrudController::class, 'updateTarget'])->name('admin.strategy.targets.update');
        Route::delete('/admin/strategy/targets/{target}', [StrategyCrudController::class, 'destroyTarget'])->name('admin.strategy.targets.destroy');

        // Calendar CRUD routes
        Route::post('/admin/calendar/generate', [CalendarCrudController::class, 'generate'])->name('admin.calendar.generate');
        Route::post('/admin/calendar/quarters', [CalendarCrudController::class, 'storeQuarter'])->name('admin.calendar.quarters.store');
        Route::put('/admin/calendar/quarters/{quarter}', [CalendarCrudController::class, 'updateQuarter'])->name('admin.calendar.quarters.update');
        Route::delete('/admin/calendar/quarters/{quarter}', [CalendarCrudController::class, 'destroyQuarter'])->name('admin.calendar.quarters.destroy');
        Route::post('/admin/calendar/fortnights', [CalendarCrudController::class, 'storeFortnight'])->name('admin.calendar.fortnights.store');
        Route::get('/admin/calendar/fortnights/{fortnight}', [CalendarCrudController::class, 'showFortnight'])->name('admin.calendar.fortnights.show');
        Route::put('/admin/calendar/fortnights/{fortnight}', [CalendarCrudController::class, 'updateFortnight'])->name('admin.calendar.fortnights.update');
        Route::delete('/admin/calendar/fortnights/{fortnight}', [CalendarCrudController::class, 'destroyFortnight'])->name('admin.calendar.fortnights.destroy');
        Route::put('/admin/calendar/days/{day}', [CalendarCrudController::class, 'updateDay'])->name('admin.calendar.days.update');

        // People & Org CRUD routes
        Route::post('/admin/campuses', [PeopleCrudController::class, 'storeCampus'])->name('admin.campuses.store');
        Route::get('/admin/organization/campuses/{campus}', [PeopleCrudController::class, 'showCampus'])->name('admin.organization.campuses.show');
        Route::put('/admin/campuses/{campus}', [PeopleCrudController::class, 'updateCampus'])->name('admin.campuses.update');
        Route::delete('/admin/campuses/{campus}', [PeopleCrudController::class, 'destroyCampus'])->name('admin.campuses.destroy');
        Route::post('/admin/positions', [PeopleCrudController::class, 'storePosition'])->name('admin.positions.store');
        Route::put('/admin/positions/{position}', [PeopleCrudController::class, 'updatePosition'])->name('admin.positions.update');
        Route::delete('/admin/positions/{position}', [PeopleCrudController::class, 'destroyPosition'])->name('admin.positions.destroy');
        Route::post('/admin/departments', [PeopleCrudController::class, 'storeDepartment'])->name('admin.departments.store');
        Route::get('/admin/organization/departments/{department}', [PeopleCrudController::class, 'showDepartment'])->name('admin.organization.departments.show');
        Route::put('/admin/departments/{department}', [PeopleCrudController::class, 'updateDepartment'])->name('admin.departments.update');
        Route::delete('/admin/departments/{department}', [PeopleCrudController::class, 'destroyDepartment'])->name('admin.departments.destroy');
        Route::post('/admin/employees', [PeopleCrudController::class, 'storeEmployee'])->name('admin.employees.store');
        Route::get('/admin/employees/{employee}', [PeopleCrudController::class, 'showEmployee'])->name('admin.employees.show');
        Route::put('/admin/employees/{employee}', [PeopleCrudController::class, 'updateEmployee'])->name('admin.employees.update');
        Route::post('/admin/employees/{employee}/status', [EmployeeLifecycleController::class, 'updateStatus'])->name('admin.employees.status.update');
        Route::post('/admin/employees/{employee}/send-reset-link', [PeopleCrudController::class, 'sendResetLink'])->name('admin.employees.send-reset-link');
        Route::delete('/admin/employees/{employee}', [PeopleCrudController::class, 'destroyEmployee'])->name('admin.employees.destroy');
        Route::post('/admin/job-descriptions', [PeopleCrudController::class, 'storeJobDescription'])->name('admin.job-descriptions.store');
        Route::put('/admin/job-descriptions/{jobDescription}', [PeopleCrudController::class, 'updateJobDescription'])->name('admin.job-descriptions.update');
        Route::delete('/admin/job-descriptions/{jobDescription}', [PeopleCrudController::class, 'destroyJobDescription'])->name('admin.job-descriptions.destroy');
        Route::post('/admin/job-descriptions/{jobDescription}/versions', [PeopleCrudController::class, 'storeJobDescriptionVersion'])->name('admin.job-descriptions.versions.store');
        Route::post('/admin/job-descriptions/{jobDescription}/versions/{version}/activate', [PeopleCrudController::class, 'activateJobDescriptionVersion'])->name('admin.job-descriptions.versions.activate');

        // Performance CRUD routes
        Route::post('/admin/kpis', [PerformanceCrudController::class, 'storeKpi'])->name('admin.kpis.store');
        Route::put('/admin/kpis/{kpi}', [PerformanceCrudController::class, 'updateKpi'])->name('admin.kpis.update');
        Route::delete('/admin/kpis/{kpi}', [PerformanceCrudController::class, 'destroyKpi'])->name('admin.kpis.destroy');
        Route::post('/admin/kpis/{kpi}/approve', [PerformanceCrudController::class, 'approveKpi'])->name('admin.kpis.approve');
        Route::post('/admin/kpis/{kpi}/confirm', [PerformanceCrudController::class, 'confirmKpi'])->name('admin.kpis.confirm');

        Route::post('/admin/tasks', [PerformanceCrudController::class, 'storeTask'])->name('admin.tasks.store');
        Route::put('/admin/tasks/{task}', [PerformanceCrudController::class, 'updateTask'])->name('admin.tasks.update');
        Route::delete('/admin/tasks/{task}', [PerformanceCrudController::class, 'destroyTask'])->name('admin.tasks.destroy');

        Route::post('/admin/deliverables', [PerformanceCrudController::class, 'storeDeliverable'])->name('admin.deliverables.store');
        Route::put('/admin/deliverables/{deliverable}', [PerformanceCrudController::class, 'updateDeliverable'])->name('admin.deliverables.update');
        Route::post('/admin/deliverables/{deliverable}/toggle', [PerformanceCrudController::class, 'toggleDeliverable'])->name('admin.deliverables.toggle');
        Route::delete('/admin/deliverables/{deliverable}', [PerformanceCrudController::class, 'destroyDeliverable'])->name('admin.deliverables.destroy');

        Route::post('/admin/evaluations/periods', [PerformanceCrudController::class, 'storePeriod'])->name('admin.evaluations.periods.store');
        Route::post('/admin/evaluations/periods/generate-monthly', [PerformanceCrudController::class, 'generateMonthlyPeriods'])->name('admin.evaluations.periods.generate-monthly');
        Route::post('/admin/evaluations/periods/{period}/toggle', [PerformanceCrudController::class, 'togglePeriodStatus'])->name('admin.evaluations.periods.toggle');
        Route::post('/admin/evaluations', [PerformanceCrudController::class, 'storeEvaluation'])->name('admin.evaluations.store');
        Route::put('/admin/evaluations/{evaluation}', [PerformanceCrudController::class, 'updateEvaluation'])->name('admin.evaluations.update');
        Route::post('/admin/evaluations/ratings', [PerformanceCrudController::class, 'storeRating'])->name('admin.evaluations.ratings.store');
        Route::put('/admin/evaluations/ratings/{rating}', [PerformanceCrudController::class, 'updateRating'])->name('admin.evaluations.ratings.update');
        Route::delete('/admin/evaluations/ratings/{rating}', [PerformanceCrudController::class, 'destroyRating'])->name('admin.evaluations.ratings.destroy');
        Route::post('/admin/ai-analysis/reports', [PerformanceCrudController::class, 'storeNarrativeReport'])->name('admin.narrative-reports.store');

        // AI Analysis triggers (JSON responses — called via fetch from the Vue page)
        Route::post('/admin/ai-analysis/reports/{report}/analyze', [PerformanceCrudController::class, 'triggerNarrativeAnalysis'])->name('admin.ai-analysis.narrative.trigger');
        Route::post('/admin/ai-analysis/evaluations/{evaluation}/analyze', [PerformanceCrudController::class, 'triggerPerformanceAnalysis'])->name('admin.ai-analysis.performance.trigger');
        Route::post('/admin/ai-analysis/{analysis}/confirm', [PerformanceCrudController::class, 'confirmAnalysis'])->name('admin.ai-analysis.confirm');
        Route::delete('/admin/ai-analysis/{analysis}', [PerformanceCrudController::class, 'dismissAnalysis'])->name('admin.ai-analysis.dismiss');

        // Auto Score computation
        Route::post('/admin/evaluations/{evaluation}/auto-score', [PerformanceCrudController::class, 'computeAutoScore'])->name('admin.evaluations.auto-score');
        Route::post('/admin/evaluations/periods/{period}/auto-score-all', [PerformanceCrudController::class, 'computeAllAutoScores'])->name('admin.evaluations.auto-score-all');

        Route::post('/admin/grading/scales', [PerformanceCrudController::class, 'storeGradeScale'])->name('admin.grading.scales.store');
        Route::post('/admin/grading/bands', [PerformanceCrudController::class, 'storeGradeBand'])->name('admin.grading.bands.store');
        Route::post('/admin/grading/increments/{recommendation}/approve', [PerformanceCrudController::class, 'approveIncrement'])->name('admin.grading.increments.approve');

        // Conduct/Discipline CRUD routes
        Route::get('/admin/conduct', [ConductController::class, 'index'])->name('admin.conduct.index');
        Route::get('/admin/conduct/create', [ConductController::class, 'create'])->name('admin.conduct.create');
        Route::post('/admin/conduct', [ConductController::class, 'store'])->name('admin.conduct.store');
        Route::get('/admin/conduct/{issue}', [ConductController::class, 'show'])->name('admin.conduct.show');
        Route::get('/admin/conduct/{issue}/edit', [ConductController::class, 'edit'])->name('admin.conduct.edit');
        Route::put('/admin/conduct/{issue}', [ConductController::class, 'update'])->name('admin.conduct.update');
        Route::post('/admin/conduct/{issue}/submit', [ConductController::class, 'submit'])->name('admin.conduct.submit');
        Route::post('/admin/conduct/{issue}/approve', [ConductController::class, 'approve'])->name('admin.conduct.approve');
        Route::post('/admin/conduct/{issue}/reject', [ConductController::class, 'reject'])->name('admin.conduct.reject');
        Route::delete('/admin/conduct/{issue}', [ConductController::class, 'destroy'])->name('admin.conduct.destroy');
        Route::get('/admin/conduct/{issue}/decision/create', [ConductController::class, 'createDecision'])->name('admin.conduct.decision.create');
        Route::post('/admin/conduct/{issue}/decision', [ConductController::class, 'storeDecision'])->name('admin.conduct.decision.store');
        Route::get('/admin/conduct-decisions/{decision}', [ConductController::class, 'showDecision'])->name('admin.conduct-decisions.show');
        Route::post('/admin/conduct-decisions/{decision}/appeal', [ConductController::class, 'appeal'])->name('admin.conduct-decisions.appeal');
        Route::post('/admin/conduct-decisions/{decision}/overturn', [ConductController::class, 'overturn'])->name('admin.conduct-decisions.overturn');

        // Employee Lifecycle: Leave Requests
        Route::get('/admin/leave-requests', [EmployeeLifecycleController::class, 'leaveIndex'])->name('admin.leave.index');
        Route::get('/admin/leave-requests/create', [EmployeeLifecycleController::class, 'leaveCreate'])->name('admin.leave.create');
        Route::post('/admin/leave-requests', [EmployeeLifecycleController::class, 'leaveStore'])->name('admin.leave.store');
        Route::get('/admin/leave-requests/{leave}', [EmployeeLifecycleController::class, 'leaveShow'])->name('admin.leave.show');
        Route::post('/admin/leave-requests/{leave}/approve', [EmployeeLifecycleController::class, 'leaveApprove'])->name('admin.leave.approve');
        Route::post('/admin/leave-requests/{leave}/reject', [EmployeeLifecycleController::class, 'leaveReject'])->name('admin.leave.reject');
        Route::post('/admin/leave-requests/{leave}/cancel', [EmployeeLifecycleController::class, 'leaveCancel'])->name('admin.leave.cancel');

        // Employee Lifecycle: Terminations
        Route::get('/admin/terminations', [EmployeeLifecycleController::class, 'terminationIndex'])->name('admin.terminations.index');
        Route::get('/admin/terminations/create', [EmployeeLifecycleController::class, 'terminationCreate'])->name('admin.terminations.create');
        Route::post('/admin/terminations', [EmployeeLifecycleController::class, 'terminationStore'])->name('admin.terminations.store');
        Route::get('/admin/terminations/{termination}', [EmployeeLifecycleController::class, 'terminationShow'])->name('admin.terminations.show');
        Route::post('/admin/terminations/{termination}/finalize', [EmployeeLifecycleController::class, 'terminationFinalize'])->name('admin.terminations.finalize');

        // Employee Lifecycle: Status Changes
        Route::get('/admin/status-changes', [EmployeeLifecycleController::class, 'statusChangeIndex'])->name('admin.status-changes.index');
        Route::get('/admin/status-changes/{change}', [EmployeeLifecycleController::class, 'statusChangeShow'])->name('admin.status-changes.show');

        // Finance CRUD routes
        Route::post('/admin/payroll/periods', [FinanceCrudController::class, 'storePeriod'])->name('admin.payroll.periods.store');
        Route::post('/admin/payroll/periods/{period}/lock', [FinanceCrudController::class, 'lockPeriod'])->name('admin.payroll.periods.lock');
        Route::post('/admin/payroll/periods/{period}/pay', [FinanceCrudController::class, 'markPeriodPaid'])->name('admin.payroll.periods.pay');
        Route::post('/admin/payroll/run', [FinanceCrudController::class, 'runPayroll'])->name('admin.payroll.run.execute');
        Route::get('/admin/payroll/{period}', [FinanceCrudController::class, 'showPeriod'])->name('admin.payroll.periods.show');
        Route::post('/admin/payroll/{period}/approve', [FinanceCrudController::class, 'approvePeriod'])->name('admin.payroll.periods.approve');
        Route::post('/admin/payroll/{period}/reject', [FinanceCrudController::class, 'rejectPeriod'])->name('admin.payroll.periods.reject');
        Route::get('/admin/payslips/{payslip}/pdf', [FinanceCrudController::class, 'payslipPdf'])->name('admin.payslips.pdf');
        Route::post('/admin/tax/brackets', [FinanceCrudController::class, 'storeTaxBracket'])->name('admin.tax.brackets.store');
        Route::put('/admin/tax/brackets/{bracket}', [FinanceCrudController::class, 'updateTaxBracket'])->name('admin.tax.brackets.update');

        // Attendance Imports
        Route::get('/admin/attendance-imports', [AttendanceImportController::class, 'index'])->name('admin.attendance-imports');
        Route::get('/admin/attendance-imports/create', [AttendanceImportController::class, 'create'])->name('admin.attendance-imports.create');
        Route::post('/admin/attendance-imports', [AttendanceImportController::class, 'store'])->name('admin.attendance-imports.store');
        Route::get('/admin/attendance-imports/{attendanceImport}', [AttendanceImportController::class, 'show'])->name('admin.attendance-imports.show');
        Route::patch('/admin/attendance-imports/{attendanceImport}/rows/{row}', [AttendanceImportController::class, 'updateRow'])->name('admin.attendance-imports.rows.update');
        Route::post('/admin/attendance-imports/{attendanceImport}/approve', [AttendanceImportController::class, 'approve'])->middleware('can:approve attendance')->name('admin.attendance-imports.approve');
        Route::post('/admin/attendance-imports/{attendanceImport}/reject', [AttendanceImportController::class, 'reject'])->middleware('can:approve attendance')->name('admin.attendance-imports.reject');
        Route::delete('/admin/attendance-imports/{attendanceImport}', [AttendanceImportController::class, 'destroy'])->name('admin.attendance-imports.destroy');
        Route::post('/admin/attendance-logs/sync', [\App\Http\Controllers\Admin\AttendanceLogController::class, 'sync'])->name('admin.attendance-logs.sync');

        // Fiscal-year viewing context
        Route::post('/admin/fiscal-year', [\App\Http\Controllers\Admin\FiscalYearController::class, 'view'])->name('admin.fiscal-year.view');

        // Admin CRUD routes
        Route::post('/admin/settings', [AdminCrudController::class, 'updateSetting'])->name('admin.settings.update');
        Route::post('/admin/settings/batch', [AdminCrudController::class, 'updateSettingsBatch'])->name('admin.settings.update-batch');
        Route::post('/admin/documents', [AdminCrudController::class, 'storeDocument'])->name('admin.documents.store');
        Route::delete('/admin/documents/{document}', [AdminCrudController::class, 'destroyDocument'])->name('admin.documents.destroy');
        Route::post('/admin/organization/archive/documents', [\App\Http\Controllers\Admin\OrganizationArchiveController::class, 'store'])->name('admin.organization.archive.documents.store');
        Route::delete('/admin/organization/archive/documents/{document}', [\App\Http\Controllers\Admin\OrganizationArchiveController::class, 'destroy'])->name('admin.organization.archive.documents.destroy');
        Route::post('/admin/users/{user}/role', [AdminCrudController::class, 'updateUserRole'])->name('admin.users.role.update');
        Route::post('/admin/users/{user}/toggle', [AdminCrudController::class, 'toggleUserApproval'])->name('admin.users.toggle');
    });

    /*
    |----------------------------------------------------------------------
    | Closed days + Mass permissions (permission-gated, not President-only)
    |----------------------------------------------------------------------
    */
    Route::middleware('can:manage closed days')->group(function () {
        Route::post('/admin/closed-days', [\App\Http\Controllers\ClosedDayController::class, 'store'])->name('admin.closed-days.store');
        Route::put('/admin/closed-days/{closedDay}', [\App\Http\Controllers\ClosedDayController::class, 'update'])->name('admin.closed-days.update');
        Route::delete('/admin/closed-days/{closedDay}', [\App\Http\Controllers\ClosedDayController::class, 'destroy'])->name('admin.closed-days.destroy');
    });

    Route::get('/finance/mass-permissions', [\App\Http\Controllers\MassPermissionController::class, 'index'])
        ->middleware('can:create mass permission')->name('finance.mass-permissions');
    Route::post('/admin/mass-permissions', [\App\Http\Controllers\MassPermissionController::class, 'store'])
        ->middleware('can:create mass permission')->name('admin.mass-permissions.store');
    Route::post('/admin/mass-permissions/{massPermission}/submit', [\App\Http\Controllers\MassPermissionController::class, 'submit'])
        ->middleware('can:create mass permission')->name('admin.mass-permissions.submit');
    Route::post('/admin/mass-permissions/{massPermission}/first-approve', [\App\Http\Controllers\MassPermissionController::class, 'firstApprove'])
        ->middleware('can:approve mass permission')->name('admin.mass-permissions.first-approve');
    Route::post('/admin/mass-permissions/{massPermission}/final-approve', [\App\Http\Controllers\MassPermissionController::class, 'finalApprove'])
        ->middleware('can:approve mass permission')->name('admin.mass-permissions.final-approve');
    Route::post('/admin/mass-permissions/{massPermission}/reject', [\App\Http\Controllers\MassPermissionController::class, 'reject'])
        ->middleware('can:approve mass permission')->name('admin.mass-permissions.reject');

    // Other role landings.
    Route::get('/executive', [DashboardController::class, 'executive'])
        ->middleware('role.landing:Vice President')->name('executive.dashboard');

    Route::get('/dean', [DashboardController::class, 'dean'])
        ->middleware('role.landing:Dean of the Seminary')->name('dean.dashboard');

    Route::get('/operations', [DashboardController::class, 'operations'])
        ->middleware('role.landing:Operational Manager')->name('operations.dashboard');

    Route::get('/registrar', [DashboardController::class, 'registrar'])
        ->middleware('role.landing:Registrar')->name('registrar.dashboard');

    /*
    |----------------------------------------------------------------------
    | Department Head workspace
    |----------------------------------------------------------------------
    */
    Route::middleware('role.landing:Department Head')->group(function () {
        Route::get('/department', [DashboardController::class, 'department'])->name('department.dashboard');

        Route::get('/department/team', [DepartmentController::class, 'team'])->name('department.team');

        Route::get('/department/archive', [DepartmentController::class, 'archive'])->name('department.archive');
        Route::post('/department/documents', [DepartmentController::class, 'storeDocument'])->name('department.documents.store');
        Route::delete('/department/documents/{document}', [DepartmentController::class, 'destroyDocument'])->name('department.documents.destroy');

        Route::get('/department/tasks', [DepartmentController::class, 'tasks'])->name('department.tasks');
        Route::post('/department/tasks', [DepartmentController::class, 'storeTask'])->name('department.tasks.store');
        Route::put('/department/tasks/{task}', [DepartmentController::class, 'updateTask'])->name('department.tasks.update');
        Route::delete('/department/tasks/{task}', [DepartmentController::class, 'destroyTask'])->name('department.tasks.destroy');

        Route::get('/department/kpis', [DepartmentController::class, 'kpis'])->name('department.kpis');
        Route::post('/department/kpis/{kpi}/approve', [DepartmentController::class, 'approveKpi'])->name('department.kpis.approve');

        Route::get('/department/evaluations', [DepartmentController::class, 'evaluations'])->name('department.evaluations');
        Route::post('/department/evaluations', [DepartmentController::class, 'storeEvaluation'])->name('department.evaluations.store');
        Route::post('/department/evaluations/{evaluation}/score', [DepartmentController::class, 'scoreEvaluation'])->name('department.evaluations.score');
        Route::post('/department/evaluations/{evaluation}/auto-score', [DepartmentController::class, 'computeAutoScore'])->name('department.evaluations.auto-score');

        Route::get('/department/deliverables', [DepartmentController::class, 'deliverables'])->name('department.deliverables');
        Route::post('/department/deliverables', [DepartmentController::class, 'storeDeliverable'])->name('department.deliverables.store');
        Route::put('/department/deliverables/{deliverable}', [DepartmentController::class, 'updateDeliverable'])->name('department.deliverables.update');
        Route::post('/department/deliverables/{deliverable}/toggle', [DepartmentController::class, 'toggleDeliverable'])->name('department.deliverables.toggle');
        Route::delete('/department/deliverables/{deliverable}', [DepartmentController::class, 'destroyDeliverable'])->name('department.deliverables.destroy');

        Route::get('/department/reports', [DepartmentController::class, 'reports'])->name('department.reports');
    });

    /*
    |----------------------------------------------------------------------
    | Finance Officer dashboard
    |----------------------------------------------------------------------
    */
    Route::middleware('role.landing:Finance Officer')->group(function () {
        Route::get('/finance', [FinanceDashboardController::class, 'index'])->name('finance.dashboard');
    });

    /*
    |----------------------------------------------------------------------
    | Payroll workspace
    |----------------------------------------------------------------------
    */
    Route::middleware('can:manage payroll')->group(function () {
        Route::get('/finance/payroll', [FinancePayrollController::class, 'index'])->name('finance.payroll');
        Route::get('/finance/payroll/{period}', [FinancePayrollController::class, 'show'])->name('finance.payroll.show');
        Route::post('/finance/payroll/{period}/run', [FinancePayrollController::class, 'run'])->middleware('can:prepare payroll')->name('finance.payroll.run');
        Route::post('/finance/payroll/{period}/submit', [FinancePayrollController::class, 'submit'])->middleware('can:submit payroll')->name('finance.payroll.submit');
        Route::post('/finance/payroll/{period}/assignments', [FinancePayrollController::class, 'storeAssignment'])->middleware('can:manage deductions')->name('finance.payroll.assignments.store');
        Route::delete('/finance/payroll/{period}/assignments/{assignment}', [FinancePayrollController::class, 'destroyAssignment'])->middleware('can:manage deductions')->name('finance.payroll.assignments.destroy');
        Route::get('/finance/payroll/{period}/export/excel', [FinancePayrollController::class, 'exportExcel'])->middleware('can:export payroll')->name('finance.payroll.export.excel');
        Route::get('/finance/payroll/{period}/export/pdf', [FinancePayrollController::class, 'exportPdf'])->middleware('can:export payroll')->name('finance.payroll.export.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | Attendance upload
    |----------------------------------------------------------------------
    */
    Route::middleware('can:upload attendance')->group(function () {
        Route::get('/finance/attendance-imports', [AttendanceImportController::class, 'index'])->name('finance.attendance-imports');
        Route::get('/finance/attendance-imports/create', [AttendanceImportController::class, 'create'])->name('finance.attendance-imports.create');
        Route::post('/finance/attendance-imports', [AttendanceImportController::class, 'store'])->name('finance.attendance-imports.store');
        Route::get('/finance/attendance-imports/{attendanceImport}', [AttendanceImportController::class, 'show'])->name('finance.attendance-imports.show');
        Route::patch('/finance/attendance-imports/{attendanceImport}/rows/{row}', [AttendanceImportController::class, 'updateRow'])->name('finance.attendance-imports.rows.update');
        Route::delete('/finance/attendance-imports/{attendanceImport}', [AttendanceImportController::class, 'destroy'])->name('finance.attendance-imports.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Attendance permissions (excused absence)
    |----------------------------------------------------------------------
    */
    Route::get('/finance/attendance-permissions', [AttendancePermissionController::class, 'index'])
        ->middleware('can:create attendance permission')->name('finance.attendance-permissions');
    Route::post('/attendance-permissions', [AttendancePermissionController::class, 'store'])
        ->middleware('can:create attendance permission')->name('attendance-permissions.store');
    Route::post('/attendance-permissions/{permission}/approve', [AttendancePermissionController::class, 'approve'])
        ->middleware('can:approve attendance permission')->name('attendance-permissions.approve');
    Route::post('/attendance-permissions/{permission}/reject', [AttendancePermissionController::class, 'reject'])
        ->middleware('can:approve attendance permission')->name('attendance-permissions.reject');
    Route::get('/attendance-permissions/{permission}/file', [AttendancePermissionController::class, 'downloadFile'])
        ->name('attendance-permissions.file');
    Route::get('/attendance-imports/{attendanceImport}/file', [AttendanceImportController::class, 'downloadFile'])
        ->name('attendance-imports.file');

    /*
    |----------------------------------------------------------------------
    | Payroll configuration — Admin only
    |----------------------------------------------------------------------
    */
    Route::middleware('can:configure payroll')->group(function () {
        Route::post('/admin/payroll/components', [PayrollConfigController::class, 'store'])->name('admin.payroll.components.store');
        Route::put('/admin/payroll/components/{component}', [PayrollConfigController::class, 'update'])->name('admin.payroll.components.update');
        Route::delete('/admin/payroll/components/{component}', [PayrollConfigController::class, 'destroy'])->name('admin.payroll.components.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Employee self-service portal (ERP). /dashboard is the ERP employee
    | landing; the public website's launch-pad lives at /portal.
    |----------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard');
    Route::get('/dashboard/tasks', [EmployeeController::class, 'tasks'])->name('employee.tasks');
    Route::post('/dashboard/tasks', [EmployeeController::class, 'storeTask'])->name('employee.tasks.store');
    Route::put('/dashboard/tasks/{task}', [EmployeeController::class, 'updateTask'])->name('employee.tasks.update');
    Route::post('/dashboard/tasks/{task}/progress', [EmployeeController::class, 'updateProgress'])->name('employee.tasks.progress');
    Route::delete('/dashboard/tasks/{task}', [EmployeeController::class, 'destroyTask'])->name('employee.tasks.destroy');
    Route::get('/dashboard/kpis', [EmployeeController::class, 'kpis'])->name('employee.kpis');
    Route::get('/dashboard/evaluations', [EmployeeController::class, 'evaluations'])->name('employee.evaluations');
    Route::get('/dashboard/payslips', [EmployeeController::class, 'payslips'])->name('employee.payslips');
    Route::get('/dashboard/payslips/{payslip}/pdf', [EmployeeController::class, 'payslipPdf'])->name('employee.payslips.pdf');
    Route::get('/dashboard/profile', [EmployeeController::class, 'profile'])->name('employee.profile');
    Route::post('/dashboard/profile/password', [EmployeeController::class, 'updatePassword'])->name('employee.password.update');

    // Admin approval of a pending user (President / Super Admin action).
    Route::post('/users/{user}/approve', [AuthController::class, 'approve'])->name('users.approve');
});
