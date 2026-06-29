<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceRowMatchMethod;
use App\Enums\AttendanceRowMatchStatus;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Resolves a parsed attendance row to a system Employee.
 *
 * Device "Employee ID"s are specific to the biometric device and unrelated
 * to the system's own staff_no, so the first import for a given device ID
 * has to go via name matching; every later import resolves instantly via the
 * cached Employee::device_employee_code (see resolve()).
 */
class EmployeeMatcher
{
    private const AUTO_ACCEPT_THRESHOLD = 92.0;
    private const AUTO_ACCEPT_MARGIN = 3.0;
    private const AMBIGUOUS_THRESHOLD = 60.0;

    /** Employee IDs already claimed by an earlier row in the current import batch. */
    private array $claimed = [];

    /** @var Collection<int, Employee> */
    private Collection $employees;

    public function __construct()
    {
        $this->employees = Employee::all(['id', 'full_name_en', 'device_employee_code', 'attendance_exempt', 'attendance_exempt_reason']);
    }

    /**
     * @param array<string, mixed> $row as produced by HikvisionAttendanceParser
     * @return array<string, mixed> resolution fields to merge into the row
     */
    public function resolve(array $row, ?Carbon $periodStart, ?Carbon $periodEnd): array
    {
        $employee = $this->matchByDeviceCode($row['device_employee_code']);

        if ($employee) {
            return $this->matched($employee, $row, AttendanceRowMatchMethod::DeviceCode, 100.0, $periodStart, $periodEnd);
        }

        [$best, $bestScore, $secondScore] = $this->bestNameCandidate($row['device_name']);

        $isConfidentUnique = $best
            && $bestScore >= self::AUTO_ACCEPT_THRESHOLD
            && ($bestScore - $secondScore) >= self::AUTO_ACCEPT_MARGIN
            && ! in_array($best->id, $this->claimed, true);

        if ($isConfidentUnique) {
            $method = $bestScore >= 99.9 ? AttendanceRowMatchMethod::NameExact : AttendanceRowMatchMethod::NameFuzzy;

            return $this->matched($best, $row, $method, $bestScore, $periodStart, $periodEnd);
        }

        $status = $best && $bestScore >= self::AMBIGUOUS_THRESHOLD
            ? AttendanceRowMatchStatus::Ambiguous
            : AttendanceRowMatchStatus::Unmatched;

        return [
            'employee_id' => null,
            'match_status' => $status,
            'match_method' => null,
            'match_confidence' => $best ? round($bestScore, 2) : null,
            'is_excluded' => false,
            'exclusion_reason' => null,
            'suggested_permitted_days' => 0,
        ];
    }

    private function matchByDeviceCode(string $deviceEmployeeCode): ?Employee
    {
        return $this->employees
            ->first(fn (Employee $e) => $e->device_employee_code === $deviceEmployeeCode
                && ! in_array($e->id, $this->claimed, true));
    }

    /** @return array{0: ?Employee, 1: float, 2: float} [best, bestScore, secondScore] */
    private function bestNameCandidate(string $deviceName): array
    {
        $target = $this->normalize($deviceName);

        $scored = $this->employees
            ->reject(fn (Employee $e) => in_array($e->id, $this->claimed, true))
            ->map(function (Employee $e) use ($target) {
                similar_text($target, $this->normalize($e->full_name_en), $percent);

                return ['employee' => $e, 'score' => $percent];
            })
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            return [null, 0.0, 0.0];
        }

        return [
            $scored[0]['employee'],
            $scored[0]['score'],
            $scored->count() > 1 ? $scored[1]['score'] : 0.0,
        ];
    }

    private function matched(
        Employee $employee,
        array $row,
        AttendanceRowMatchMethod $method,
        float $confidence,
        ?Carbon $periodStart,
        ?Carbon $periodEnd,
    ): array {
        $this->claimed[] = $employee->id;

        if ($method !== AttendanceRowMatchMethod::DeviceCode && ! $employee->device_employee_code) {
            $employee->update(['device_employee_code' => $row['device_employee_code']]);
        }

        return [
            'employee_id' => $employee->id,
            'match_status' => AttendanceRowMatchStatus::Matched,
            'match_method' => $method,
            'match_confidence' => round($confidence, 2),
            'is_excluded' => $employee->attendance_exempt,
            'exclusion_reason' => $employee->attendance_exempt ? ($employee->attendance_exempt_reason ?: 'Employee is marked exempt from device attendance.') : null,
            'suggested_permitted_days' => $this->approvedLeaveDays($employee, $periodStart, $periodEnd),
        ];
    }

    private function approvedLeaveDays(Employee $employee, ?Carbon $periodStart, ?Carbon $periodEnd): int
    {
        if (! $periodStart || ! $periodEnd) {
            return 0;
        }

        return (int) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveStatus::Approved)
            ->where('start_date', '<=', $periodEnd)
            ->where('end_date', '>=', $periodStart)
            ->sum('days_approved');
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper($value)));
    }
}
