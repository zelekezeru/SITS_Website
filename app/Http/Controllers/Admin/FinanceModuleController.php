<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AttendancePermissionController;
use App\Http\Controllers\ClosedDayController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeeLoanController;
use App\Http\Controllers\MassPermissionController;
use App\Models\AttendanceImport;
use App\Models\AttendanceLog;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\TaxBracket;
use App\Support\AdminNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the admin Finance module landing pages (payroll, attendance, payslips,
 * tax, closed days, mass/attendance permissions). Extracted from ModuleController
 * to keep that dispatcher small; it delegates here for any `admin.payroll.*` /
 * attendance / payslip / tax / closed-day / mass-permission route.
 */
class FinanceModuleController extends Controller
{
    /** @return Response|null Inertia response, or null if the route isn't a finance page. */
    public function render(Request $request, string $routeName, ?array $module): ?Response
    {
        $base = ['module' => $module, 'nav' => AdminNavigation::sections()];

        if (in_array($routeName, ['admin.payroll', 'admin.payroll.periods', 'admin.payroll.run'], true)) {
            return Inertia::render('Admin/Finance/Payroll', $base + [
                'routeName' => $routeName,
                'periods' => PayrollPeriod::monthly()->forActiveYear()->with('payslips')->orderByDesc('start_date')->get(),
                'payslips' => Payslip::with(['employee', 'payrollPeriod'])->get(),
            ]);
        }

        if ($routeName === 'admin.payroll.components') {
            return Inertia::render('Admin/Finance/PayrollComponents', $base + [
                'components' => PayrollComponent::orderBy('kind')->orderBy('sort_order')->orderBy('name')->get(),
                'meta' => PayrollConfigController::meta(),
            ]);
        }

        if ($routeName === 'admin.attendance-permissions') {
            return Inertia::render('Admin/Finance/AttendancePermissions/Index',
                $base + AttendancePermissionController::pageProps($request->user()));
        }

        if ($routeName === 'admin.closed-days') {
            return Inertia::render('Admin/Finance/ClosedDays/Index',
                $base + ClosedDayController::pageProps($request->user()));
        }

        if ($routeName === 'admin.mass-permissions') {
            return Inertia::render('Admin/Finance/MassPermissions/Index',
                $base + MassPermissionController::pageProps($request->user()));
        }

        if ($routeName === 'admin.loans') {
            return Inertia::render('Admin/Finance/Loans/Index',
                $base + EmployeeLoanController::pageProps($request->user()));
        }

        if ($routeName === 'admin.attendance') {
            $durationType = request('duration_type', 'this_month');
            $startDate = request('start_date');
            $endDate = request('end_date');

            $now = \Carbon\Carbon::now();
            if ($durationType === 'last_month') {
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
            } elseif ($durationType === 'custom') {
                $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth();
                $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfMonth();
            } else { // this_month
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
            }

            // Stored monthly summaries whose period overlaps the range (payroll-authoritative).
            $records = AttendanceRecord::whereHas('payrollPeriod', function ($q) use ($start, $end) {
                    $q->where('start_date', '<=', $end)->where('end_date', '>=', $start);
                })
                ->with(['employee', 'payrollPeriod'])
                ->get()
                ->sortBy(fn ($r) => optional($r->payrollPeriod)->start_date);

            // Raw real-time swipes in the range (device push / webhook), newest first.
            $logs = AttendanceLog::whereBetween('swipe_time', [$start, $end])
                ->with('employee')->latest('swipe_time')->get();

            // Roll live swipes up per matched employee: how many punches, on how many
            // distinct days, and when they were last seen on a terminal.
            $liveByEmployee = $logs->whereNotNull('employee_id')
                ->groupBy('employee_id')
                ->map(fn ($g) => [
                    'swipes' => $g->count(),
                    'days_present' => $g->map(fn ($l) => $l->swipe_time->format('Y-m-d'))->unique()->count(),
                    'last_seen' => optional($g->max('swipe_time'))->toDateTimeString(),
                ]);

            // Most-recent stored record per employee within the range.
            $recordByEmployee = $records->keyBy('employee_id');

            // Merge live + stored into one per-employee reconciliation row so the
            // real-time device signal and the payroll-stored summary sit side by side.
            $merged = Employee::where('is_active', true)->orderBy('full_name_en')->get()
                ->map(function ($e) use ($recordByEmployee, $liveByEmployee) {
                    $rec = $recordByEmployee->get($e->id);
                    $live = $liveByEmployee->get($e->id);

                    return [
                        'employee_id' => $e->id,
                        'name' => $e->full_name_en,
                        'staff_no' => $e->staff_no,
                        'attendance_exempt' => (bool) $e->attendance_exempt,
                        'stored' => $rec ? [
                            'period' => $rec->payrollPeriod?->name,
                            'work_hours' => (float) $rec->work_hours,
                            'late_minutes' => (int) $rec->late_minutes,
                            'absent_days' => (int) $rec->absent_days,
                            'permitted_days' => (int) $rec->permitted_days,
                            'ot_normal' => (float) $rec->overtime_normal,
                            'ot_night' => (float) $rec->ot_night,
                            'ot_rest' => (float) $rec->ot_rest,
                            'ot_holiday' => (float) $rec->ot_holiday,
                            'source' => $rec->source?->value,
                            'status' => $rec->status?->value,
                        ] : null,
                        'live' => $live ?: null,
                    ];
                })->values();

            return Inertia::render('Admin/Finance/Attendance', $base + [
                'merged' => $merged,
                'attendanceRecords' => $records->values(),
                'attendanceLogs' => $logs,
                'payrollPeriods' => PayrollPeriod::monthly()->forActiveYear()->orderByDesc('start_date')->get(),
                'filters' => [
                    'duration_type' => $durationType,
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                ]
            ]);
        }

        if ($routeName === 'admin.attendance-exemptions') {
            $employees = Employee::with('department')
                ->orderByDesc('attendance_exempt')
                ->orderBy('full_name_en')
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->full_name_en,
                    'staff_no' => $e->staff_no,
                    'grade' => $e->grade,
                    'department' => $e->department?->name_en,
                    'is_active' => (bool) $e->is_active,
                    'attendance_exempt' => (bool) $e->attendance_exempt,
                    'attendance_exempt_reason' => $e->attendance_exempt_reason,
                ]);

            return Inertia::render('Admin/Finance/AttendanceExemptions/Index', $base + [
                'employees' => $employees,
                'stats' => [
                    'total' => $employees->count(),
                    'exempt' => $employees->where('attendance_exempt', true)->count(),
                    'tracked' => $employees->where('attendance_exempt', false)->count(),
                ],
            ]);
        }

        if ($routeName === 'admin.attendance-imports') {
            return Inertia::render('Admin/Finance/AttendanceImports/Index', $base + [
                'imports' => AttendanceImport::with(['payrollPeriod', 'reviewedBy'])->latest()->get(),
            ]);
        }

        if ($routeName === 'admin.payslips') {
            return Inertia::render('Admin/Finance/Payslips', $base + [
                'payslips' => Payslip::with(['employee', 'payrollPeriod', 'lines'])
                    ->latest()->paginate(25)->withQueryString(),
            ]);
        }

        if ($routeName === 'admin.tax') {
            return Inertia::render('Admin/Finance/TaxConfig', $base + [
                'brackets' => TaxBracket::orderBy('min_income', 'asc')->get(),
            ]);
        }

        return null;
    }
}
