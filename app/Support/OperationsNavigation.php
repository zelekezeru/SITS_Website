<?php

namespace App\Support;

/**
 * Sidebar for the Operations Manager portal. Operations shares the payroll and
 * attendance tools with Finance (run payroll, upload attendance, raise excused
 * absences) and also sees the employee self-service sections.
 */
class OperationsNavigation
{
    /**
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    public static function sections(): array
    {
        return array_merge([
            [
                'label' => 'Overview',
                'items' => [
                    self::item('Operations', 'operations.dashboard', '/operations', 'LayoutDashboard',
                        'Attendance, KPIs and departmental delivery.'),
                ],
            ],
            [
                'label' => 'Payroll & Attendance',
                'items' => [
                    self::item('Payroll Periods', 'finance.payroll', '/finance/payroll', 'Banknote',
                        'Prepare monthly payroll, assign components, submit for approval and export.'),
                    self::item('Attendance Upload', 'finance.attendance-imports', '/finance/attendance-imports', 'UploadCloud',
                        'Upload device attendance for review before it posts to payroll.'),
                    self::item('Attendance Permissions', 'finance.attendance-permissions', '/finance/attendance-permissions', 'CalendarCheck',
                        'Raise excused-absence requests for the Admin to approve.'),
                    self::item('Mass Permissions', 'finance.mass-permissions', '/finance/mass-permissions', 'CalendarRange',
                        'Batch excused-absence for closed days, for two-person approval.'),
                ],
            ],
        ], FinanceNavigation::selfServiceSections());
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
