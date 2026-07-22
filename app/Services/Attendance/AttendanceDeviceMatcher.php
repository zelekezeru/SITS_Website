<?php

namespace App\Services\Attendance;

use App\Models\Employee;

/**
 * Resolves a live HikVision punch to an employee by the name the terminal sends,
 * so `device_employee_code` self-populates from the first punch and later
 * punches match instantly by code.
 *
 * Deliberately conservative: in a real-time path there is no human review step
 * (unlike the Excel importer's {@see EmployeeMatcher}), so it only auto-links on
 * an EXACT, UNIQUE name match to an employee who has no device code yet. Anything
 * ambiguous is left for manual reconciliation rather than risk attributing
 * attendance to the wrong person.
 */
class AttendanceDeviceMatcher
{
    public function resolveByName(?string $name, string $deviceCode): ?Employee
    {
        $name = trim((string) $name);
        if ($name === '' || $deviceCode === '') {
            return null;
        }

        // Never hijack a code already claimed by someone else.
        if (Employee::where('device_employee_code', $deviceCode)->exists()) {
            return null;
        }

        $normalized = $this->normalize($name);

        $candidates = Employee::whereNull('device_employee_code')
            ->get(['id', 'full_name_en', 'full_name_am'])
            ->filter(fn (Employee $e) => $this->normalize((string) $e->full_name_en) === $normalized
                || $this->normalize((string) $e->full_name_am) === $normalized)
            ->values();

        // Require a single unambiguous match — skip if 0 or >1.
        if ($candidates->count() !== 1) {
            return null;
        }

        $employee = $candidates->first();
        $employee->update(['device_employee_code' => $deviceCode]);

        return $employee;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper($value)));
    }
}
