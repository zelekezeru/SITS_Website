<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceLogController extends Controller
{
    /**
     * Display a listing of live biometric attendance logs and HikVision webhook events.
     */
    public function index(Request $request)
    {
        $search = (string) $request->input('search', '');
        $direction = (string) $request->input('direction', '');
        $match = (string) $request->input('match', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = AttendanceLog::with('employee')->latest('swipe_time');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('device_employee_code', 'like', "%{$search}%")
                  ->orWhere('device_name', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('full_name_en', 'like', "%{$search}%")
                         ->orWhere('full_name_am', 'like', "%{$search}%")
                         ->orWhere('employee_id', 'like', "%{$search}%");
                  });
            });
        }

        if (in_array($direction, ['in', 'out', 'unknown'], true)) {
            $query->where('direction', $direction);
        }

        if ($match === 'matched') {
            $query->whereNotNull('employee_id');
        } elseif ($match === 'unmatched') {
            $query->whereNull('employee_id');
        }

        if (!empty($startDate)) {
            $query->whereDate('swipe_time', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('swipe_time', '<=', $endDate);
        }

        $logs = $query->paginate(30)->withQueryString();

        $stats = [
            'total_swipes' => AttendanceLog::count(),
            'today_swipes' => AttendanceLog::whereDate('swipe_time', now()->today())->count(),
            'matched_swipes' => AttendanceLog::whereNotNull('employee_id')->count(),
            'unmatched_swipes' => AttendanceLog::whereNull('employee_id')->count(),
            'is_authenticated' => (string) config('services.hikvision.webhook_secret', '') !== '',
            'webhook_url' => url('/hikvision/webhook'),
        ];

        return Inertia::render('Admin/Finance/AttendanceLogs/Index', [
            'module' => [
                'section' => 'Finance & HR',
                'label' => 'Biometric & Webhook Logs',
                'description' => 'Real-time attendance swipes received from internet-connected HikVision access terminals.',
            ],
            'attendanceLogs' => $logs,
            'stats' => $stats,
            'employees' => Employee::orderBy('full_name_en')->get(['id', 'full_name_en', 'employee_id', 'device_employee_code']),
            'payrollPeriods' => PayrollPeriod::monthly()->forActiveYear()->orderByDesc('start_date')->get(),
            'filters' => [
                'search' => $search,
                'direction' => $direction,
                'match' => $match,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
        ]);
    }

    /**
     * Aggregate and sync raw biometric logs into employee monthly attendance summaries.
     */
    public function sync(Request $request, AttendanceSyncService $service)
    {
        $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
        ]);

        $period = PayrollPeriod::findOrFail($request->input('payroll_period_id'));

        if ($period->isLocked()) {
            return redirect()->back()->with('error', 'Cannot sync attendance into a locked or paid payroll period.');
        }

        $result = $service->sync($period);

        return redirect()->back()->with('success', "Successfully aggregated and synced {$result['synced']} employee attendance records from real-time logs.");
    }

    /**
     * Manually link an unmatched device code to an employee, then back-fill every
     * past log carrying that code so history attributes to the right person and
     * all future punches resolve automatically.
     */
    public function reconcile(Request $request)
    {
        $data = $request->validate([
            'device_employee_code' => ['required', 'string'],
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        // Guard against stealing a code already claimed by someone else.
        $conflict = Employee::where('device_employee_code', $data['device_employee_code'])
            ->where('id', '!=', $employee->id)
            ->first();

        if ($conflict) {
            return redirect()->back()->with('error', "Device code {$data['device_employee_code']} is already linked to {$conflict->full_name_en}.");
        }

        $employee->update(['device_employee_code' => $data['device_employee_code']]);

        $backfilled = AttendanceLog::where('device_employee_code', $data['device_employee_code'])
            ->whereNull('employee_id')
            ->update(['employee_id' => $employee->id]);

        return redirect()->back()->with('success', "Linked code {$data['device_employee_code']} to {$employee->full_name_en} and updated {$backfilled} past log(s).");
    }
}
