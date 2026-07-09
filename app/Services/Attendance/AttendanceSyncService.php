<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\AttendanceRecord;
use App\Models\PayrollPeriod;
use App\Models\LeaveRequest;
use App\Models\ClosedDay;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AttendanceSyncService
{
    /**
     * Synchronizes and aggregates raw attendance logs for a payroll period.
     *
     * @return array{synced: int, errors: array}
     */
    public function sync(PayrollPeriod $period): array
    {
        $start = $period->start_date->startOfDay();
        $end = $period->end_date->endOfDay();

        // 1. Get all closed/holiday dates in the period. Closed days are stored
        //    as inclusive start_date → end_date ranges, so pull any range that
        //    overlaps the period and expand it into individual calendar dates.
        $closedDates = ClosedDay::where('is_active', true)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get()
            ->flatMap(fn ($d) => $d->dates())
            ->unique()
            ->values()
            ->toArray();

        // 2. Generate list of working calendar days (Monday-Friday, excluding holidays)
        $workingDays = [];
        $carbonPeriod = CarbonPeriod::create($start, $end);
        foreach ($carbonPeriod as $date) {
            $dateStr = $date->format('Y-m-d');
            if ($date->isWeekday() && !in_array($dateStr, $closedDates)) {
                $workingDays[] = $dateStr;
            }
        }

        // 3. Get all active employees who are not exempt from attendance
        $employees = Employee::where('attendance_exempt', false)->get();

        $syncedCount = 0;
        $errors = [];

        DB::transaction(function () use ($period, $start, $end, $workingDays, $employees, &$syncedCount) {
            foreach ($employees as $employee) {
                // Fetch all raw logs for the employee in the period
                $logs = AttendanceLog::where('employee_id', $employee->id)
                    ->whereBetween('swipe_time', [$start, $end])
                    ->orderBy('swipe_time')
                    ->get();

                // Group logs by date string
                $logsByDate = $logs->groupBy(function ($log) {
                    return $log->swipe_time->format('Y-m-d');
                });

                // Fetch overlapping approved leaves
                $leaves = LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', LeaveStatus::Approved)
                    ->where('start_date', '<=', $end)
                    ->where('end_date', '>=', $start)
                    ->get();

                $totalWorkHours = 0.0;
                $totalLateMinutes = 0;
                $totalAbsentDays = 0;
                $totalPermittedDays = 0;

                // Process each day in the payroll period
                $carbonPeriod = CarbonPeriod::create($start, $end);
                foreach ($carbonPeriod as $date) {
                    $dateStr = $date->format('Y-m-d');
                    
                    // Check if employee has an approved leave for this date
                    $isOnLeave = $leaves->contains(function ($leave) use ($date) {
                        return $date->between($leave->start_date, $leave->end_date);
                    });

                    if ($isOnLeave) {
                        // Excused absence / permitted day
                        if ($date->isWeekday() && !in_array($dateStr, $workingDays)) {
                            // Leave day on weekend/holiday doesn't add to permitted
                        } else {
                            $totalPermittedDays++;
                        }
                        continue;
                    }

                    // Check if it is a working day
                    $isWorkingDay = in_array($dateStr, $workingDays);

                    // Fetch logs for this specific date
                    $dayLogs = $logsByDate->get($dateStr);

                    if ($dayLogs && $dayLogs->count() > 0) {
                        $firstLog = $dayLogs->first();
                        $lastLog = $dayLogs->last();

                        // Calculate late minutes (Standard start is 8:30 AM)
                        // Allow a 5-minute grace period (up to 8:35 AM)
                        if ($isWorkingDay) {
                            $arrivalTime = Carbon::parse($firstLog->swipe_time);
                            $standardStart = Carbon::parse($dateStr . ' 08:30:00');
                            $graceLimit = Carbon::parse($dateStr . ' 08:35:00');

                            if ($arrivalTime->gt($graceLimit)) {
                                $totalLateMinutes += $arrivalTime->diffInMinutes($standardStart);
                            }
                        }

                        // Calculate actual hours worked (Time between first and last swipe)
                        if ($dayLogs->count() >= 2) {
                            $inTime = Carbon::parse($firstLog->swipe_time);
                            $outTime = Carbon::parse($lastLog->swipe_time);
                            
                            $durationMinutes = $outTime->diffInMinutes($inTime);
                            
                            // Deduct 1 hour (60 minutes) for lunch if worked more than 5 hours
                            if ($durationMinutes > 300) {
                                $durationMinutes -= 60;
                            }
                            
                            $totalWorkHours += round(max(0, $durationMinutes) / 60, 2);
                        } else {
                            // Only 1 swipe recorded: count as standard half-day shift (4 hours)
                            $totalWorkHours += 4.0;
                        }
                    } else {
                        // No logs recorded
                        if ($isWorkingDay) {
                            $totalAbsentDays++;
                        }
                    }
                }

                // Update or create monthly attendance summary record
                AttendanceRecord::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'payroll_period_id' => $period->id,
                    ],
                    [
                        'work_hours' => $totalWorkHours,
                        'late_minutes' => $totalLateMinutes,
                        'absent_days' => $totalAbsentDays,
                        'permitted_days' => $totalPermittedDays,
                        'source' => AttendanceSource::DeviceFetch,
                        'status' => AttendanceStatus::Verified,
                    ]
                );

                $syncedCount++;
            }
        });

        return [
            'synced' => $syncedCount,
            'errors' => $errors,
        ];
    }
}
