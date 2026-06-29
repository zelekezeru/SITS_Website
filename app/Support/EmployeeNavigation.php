<?php

namespace App\Support;

/**
 * Sidebar navigation for the Employee self-service portal.
 *
 * Mirrors the shape consumed by the shared shell (Layouts/AdminLayout.vue):
 * sections → items → { name, label, path, icon }. Kept deliberately lean —
 * an employee only sees their own work, growth, pay and profile.
 */
class EmployeeNavigation
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
                    self::item('My Dashboard', 'dashboard', '/dashboard', 'LayoutDashboard',
                        'Your personal performance snapshot for the current fortnight.'),
                ],
            ],
            [
                'label' => 'My Work',
                'items' => [
                    self::item('My Tasks', 'employee.tasks', '/dashboard/tasks', 'ListChecks',
                        'Plan, progress and complete the tasks assigned to you.'),
                    self::item('My KPIs', 'employee.kpis', '/dashboard/kpis', 'Gauge',
                        'The key performance indicators you are measured against.'),
                ],
            ],
            [
                'label' => 'My Growth',
                'items' => [
                    self::item('My Evaluations', 'employee.evaluations', '/dashboard/evaluations', 'Star',
                        'Your multi-rater scorecards, grades and feedback.'),
                ],
            ],
            [
                'label' => 'Finance',
                'items' => [
                    self::item('My Payslips', 'employee.payslips', '/dashboard/payslips', 'ReceiptText',
                        'Your monthly payslips with earnings and deductions.'),
                ],
            ],
            [
                'label' => 'Account',
                'items' => [
                    self::item('My Profile', 'employee.profile', '/dashboard/profile', 'CircleUserRound',
                        'Your personnel record and account security.'),
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
            'section' => null,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
