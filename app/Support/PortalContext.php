<?php

namespace App\Support;

use App\Enums\EvaluationStatus;
use App\Enums\IncrementStatus;
use App\Enums\KpiStatus;
use App\Enums\TaskStatus;
use App\Models\Evaluation;
use App\Models\IncrementRecommendation;
use App\Models\Kpi;
use App\Models\Task;
use App\Models\User;

/**
 * Single source of truth for the per-role portal shell: which sidebar a user
 * sees, the brand subtitle, and the actionable notifications surfaced in the
 * topbar. Shared on every request by HandleInertiaRequests so the shell is
 * identical across the President admin area, the Department Head workspace and
 * the Employee self-service portal.
 */
class PortalContext
{
    /**
     * @return array{nav: array<int, mixed>, portal: ?array<string, mixed>, notifications: array<int, array<string, mixed>>}
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return ['nav' => [], 'portal' => null, 'notifications' => []];
        }

        return match (RoleLanding::resolvedRole($user)) {
            'President / Super Admin' => [
                'nav' => AdminNavigation::sections(),
                'portal' => self::portal('President · Super Admin', '/admin', 'blue'),
                'notifications' => self::presidentNotifications(),
            ],
            'Department Head' => [
                'nav' => DepartmentNavigation::sections(),
                'portal' => self::portal('Department Head', '/department', 'emerald'),
                'notifications' => self::departmentNotifications($user),
            ],
            'Finance Officer' => [
                'nav' => FinanceNavigation::sections(),
                'portal' => self::portal('Finance · Officer', '/finance', 'teal'),
                'notifications' => self::financeNotifications(),
            ],
            'Operational Manager' => [
                'nav' => OperationsNavigation::sections(),
                'portal' => self::portal('Operations Manager', '/operations', 'cyan'),
                'notifications' => self::financeNotifications(),
            ],
            'Vice President', 'Dean of the Seminary', 'Registrar' => [
                'nav' => self::minimalNav($user),
                'portal' => self::portal(RoleLanding::resolvedRole($user), RoleLanding::url($user), 'indigo'),
                'notifications' => [],
            ],
            // Employee and any unrecognized role land on the self-service portal.
            default => [
                'nav' => EmployeeNavigation::sections(),
                'portal' => self::portal('My Workspace', '/dashboard', 'blue'),
                'notifications' => self::employeeNotifications($user),
            ],
        };
    }

    /** @return array<string, mixed> */
    private static function portal(string $roleLabel, string $home, string $accent): array
    {
        return ['roleLabel' => $roleLabel, 'home' => $home, 'accent' => $accent];
    }

    /** A single Dashboard link for roles whose full portal isn't built yet. */
    private static function minimalNav(User $user): array
    {
        return [[
            'label' => 'Overview',
            'items' => [[
                'label' => 'Dashboard',
                'name' => RoleLanding::routeName($user),
                'path' => parse_url(RoleLanding::url($user), PHP_URL_PATH),
                'icon' => 'LayoutDashboard',
            ]],
        ]];
    }

    // ----- Notifications --------------------------------------------------

    /**
     * Actionable items the President should act on, derived from live state.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function presidentNotifications(): array
    {
        $items = [];

        $pendingUsers = User::where('is_approved', false)->count();
        if ($pendingUsers > 0) {
            $items[] = self::note('pending-users', 'Account approvals',
                $pendingUsers.' account'.self::s($pendingUsers).' awaiting approval',
                $pendingUsers, '/admin/users/approvals', 'KeyRound', 'amber');
        }

        $kpisToApprove = Kpi::whereNull('approved_by')->count();
        if ($kpisToApprove > 0) {
            $items[] = self::note('kpis-approve', 'KPIs to approve',
                $kpisToApprove.' KPI'.self::s($kpisToApprove).' awaiting the maker step',
                $kpisToApprove, '/admin/kpis/approvals', 'Gauge', 'blue');
        }

        $kpisToConfirm = Kpi::whereNotNull('approved_by')->whereNull('confirmed_by')->count();
        if ($kpisToConfirm > 0) {
            $items[] = self::note('kpis-confirm', 'KPIs to confirm',
                $kpisToConfirm.' KPI'.self::s($kpisToConfirm).' awaiting your confirmation',
                $kpisToConfirm, '/admin/kpis/confirmations', 'ShieldCheck', 'emerald');
        }

        $pendingIncrements = IncrementRecommendation::where('status', IncrementStatus::Pending)->count();
        if ($pendingIncrements > 0) {
            $items[] = self::note('increments', 'Salary increments',
                $pendingIncrements.' increment'.self::s($pendingIncrements).' awaiting approval',
                $pendingIncrements, '/admin/grading/increments', 'Award', 'violet');
        }

        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    private static function departmentNotifications(User $user): array
    {
        $deptIds = $user->managedDepartmentIds();
        if (empty($deptIds)) {
            return [];
        }

        $employeeIds = \App\Models\Employee::whereIn('department_id', $deptIds)->pluck('id');
        $items = [];

        $kpisToApprove = Kpi::whereNull('approved_by')
            ->whereHas('employees', fn ($q) => $q->whereIn('employees.id', $employeeIds))
            ->count();
        if ($kpisToApprove > 0) {
            $items[] = self::note('dept-kpis', 'KPIs to approve',
                $kpisToApprove.' team KPI'.self::s($kpisToApprove).' awaiting your approval',
                $kpisToApprove, '/department/kpis', 'Gauge', 'blue');
        }

        $toScore = Evaluation::whereIn('employee_id', $employeeIds)
            ->where('status', EvaluationStatus::Draft)->count();
        if ($toScore > 0) {
            $items[] = self::note('dept-evals', 'Evaluations to score',
                $toScore.' draft scorecard'.self::s($toScore).' need your input',
                $toScore, '/department/evaluations', 'Star', 'violet');
        }

        return $items;
    }

    /**
     * Surface payroll periods that bounced back from the President so the
     * Finance Officer knows to revise and resubmit.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function financeNotifications(): array
    {
        $rejected = \App\Models\PayrollPeriod::where('status', \App\Enums\PayrollStatus::Rejected)->count();
        if ($rejected === 0) {
            return [];
        }

        return [self::note('payroll-rejected', 'Payroll returned',
            $rejected.' payroll period'.self::s($rejected).' sent back for revision',
            $rejected, '/finance/payroll', 'Banknote', 'amber')];
    }

    /** @return array<int, array<string, mixed>> */
    private static function employeeNotifications(User $user): array
    {
        $employee = $user->employee;
        if (! $employee) {
            return [];
        }

        $openTasks = Task::where('employee_id', $employee->id)
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])
            ->count();

        if ($openTasks === 0) {
            return [];
        }

        return [self::note('my-tasks', 'Open tasks',
            $openTasks.' task'.self::s($openTasks).' on your plate',
            $openTasks, '/dashboard/tasks', 'ListChecks', 'blue')];
    }

    /** @return array<string, mixed> */
    private static function note(string $id, string $title, string $message, int $count, string $href, string $icon, string $tone): array
    {
        return compact('id', 'title', 'message', 'count', 'href', 'icon', 'tone');
    }

    private static function s(int $n): string
    {
        return $n === 1 ? '' : 's';
    }
}
