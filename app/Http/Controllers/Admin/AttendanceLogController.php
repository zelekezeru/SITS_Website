<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Attendance\AttendanceSyncService;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
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
}
