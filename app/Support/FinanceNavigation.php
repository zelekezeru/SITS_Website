<?php

namespace App\Support;

/**
 * Navigation tree for the Finance Officer portal. Mirrors AdminNavigation's
 * shape so the shared AdminLayout sidebar renders it unchanged.
 */
class FinanceNavigation
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
                    self::item('Dashboard', 'finance.dashboard', '/finance', 'LayoutDashboard',
                        'Payroll preparation status, headcount and pending approvals.'),
                ],
            ],
            [
                'label' => 'Payroll',
                'items' => [
                    self::item('Payroll Periods', 'finance.payroll', '/finance/payroll', 'Banknote',
                        'Monthly payroll periods: prepare, assign components, submit for approval and export.',
                        ['Monthly periods', 'Per-employee summary', 'Allowances & deductions', 'Submit for approval']),
                    self::item('Attendance Upload', 'finance.attendance-imports', '/finance/attendance-imports', 'UploadCloud',
                        'Upload HikVision attendance exports for review before they post to payroll.',
                        ['Excel upload', 'Smart employee matching', 'Pending admin approval']),
                    self::item('Attendance Permissions', 'finance.attendance-permissions', '/finance/attendance-permissions', 'CalendarCheck',
                        'Excused-absence requests approved before payroll calculation.',
                        ['Permitted days', 'Maker-checker approval']),
                    self::item('Mass Permissions', 'finance.mass-permissions', '/finance/mass-permissions', 'CalendarRange',
                        'Batch excused-absence for closed days across all employees.',
                        ['Closed-day calendar', 'Two-person approval']),
                ],
            ],
            ...self::selfServiceSections(),
        ];
    }

    /**
     * Employee self-service sections (tasks, KPIs, evaluations, payslips, profile)
     * surfaced inside the Finance and Operations portals alongside their tools.
     *
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    public static function selfServiceSections(): array
    {
        return [
            [
                'label' => 'My Work',
                'items' => [
                    self::item('My Tasks', 'employee.tasks', '/dashboard/tasks', 'ListChecks', 'Plan and progress your own tasks.'),
                    self::item('My KPIs', 'employee.kpis', '/dashboard/kpis', 'Gauge', 'The KPIs you are measured against.'),
                ],
            ],
            [
                'label' => 'My Growth',
                'items' => [
                    self::item('My Evaluations', 'employee.evaluations', '/dashboard/evaluations', 'Star', 'Your multi-rater scorecards and feedback.'),
                ],
            ],
            [
                'label' => 'Account',
                'items' => [
                    self::item('My Payslips', 'employee.payslips', '/dashboard/payslips', 'ReceiptText', 'Your own monthly payslips.'),
                    self::item('My Profile', 'employee.profile', '/dashboard/profile', 'CircleUserRound', 'Your personnel record and security.'),
                ],
            ],
        ];
    }

    /** @return array<int, array<string,mixed>> flattened leaves that need a route */
    public static function modules(): array
    {
        $flat = [];

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                $flat[] = [
                    'label' => $item['label'],
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'icon' => $item['icon'] ?? null,
                    'section' => $section['label'],
                    'description' => $item['description'] ?? null,
                    'features' => $item['features'] ?? [],
                ];
            }
        }

        return array_values(array_filter($flat, fn ($m) => $m['name'] !== 'finance.dashboard'));
    }

    public static function module(string $name): ?array
    {
        foreach (self::modules() as $module) {
            if ($module['name'] === $name) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @param array<int,string> $features
     */
    private static function item(string $label, string $name, string $path, string $icon, string $description = '', array $features = []): array
    {
        return array_filter([
            'label' => $label,
            'name' => $name,
            'path' => $path,
            'icon' => $icon,
            'description' => $description,
            'features' => $features,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
