<?php

namespace App\Support;

/**
 * Sidebar navigation for the Department Head workspace.
 *
 * Same shape as AdminNavigation / EmployeeNavigation so the shared shell
 * (Layouts/AdminLayout.vue) and command palette render it unchanged. Scoped
 * to a head's managed departments: their team, the team's tasks/KPIs,
 * evaluations they score, deliverables and a department report.
 */
class DepartmentNavigation
{
    /**
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    public static function sections(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    self::item('Dashboard', 'department.dashboard', '/department', 'LayoutDashboard',
                        'Your team at a glance: workload, approvals and scores.'),
                ],
            ],
            [
                'label' => 'People',
                'items' => [
                    self::item('My Team', 'department.team', '/department/team', 'Users',
                        'The staff reporting into your department(s).'),
                    self::item('Archive', 'department.archive', '/department/archive', 'FolderOpen',
                        'Documents, files and links for the department(s) you head.'),
                ],
            ],
            [
                'label' => 'Performance',
                'items' => [
                    self::item('Team Tasks', 'department.tasks', '/department/tasks', 'ListChecks',
                        'Assign, track and close out your team\'s fortnightly tasks.'),
                    self::item('KPI Approvals', 'department.kpis', '/department/kpis', 'Gauge',
                        'Approve created KPIs (the maker step) for your team.'),
                    self::item('Evaluations', 'department.evaluations', '/department/evaluations', 'Star',
                        'Enter manager scores on your team\'s evaluation scorecards.'),
                    self::item('Deliverables', 'department.deliverables', '/department/deliverables', 'Flag',
                        'Per-fortnight deliverables with deadlines and sign-off.'),
                ],
            ],
            [
                'label' => 'Insights',
                'items' => [
                    self::item('Department Report', 'department.reports', '/department/reports', 'PieChart',
                        'Completion, KPI and evaluation analytics for your team.'),
                ],
            ],
        ];
    }

    private static function item(string $label, string $name, string $path, string $icon, string $description = ''): array
    {
        return array_filter([
            'label' => $label,
            'name' => $name,
            'path' => $path,
            'icon' => $icon,
            'description' => $description,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
