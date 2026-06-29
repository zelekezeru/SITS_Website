<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AttendancePermissionController;
use App\Http\Controllers\ClosedDayController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MassPermissionController;
use App\Models\AttendanceImport;
use App\Models\AttendanceRecord;
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

        if ($routeName === 'admin.attendance') {
            return Inertia::render('Admin/Finance/Attendance', $base + [
                'attendanceRecords' => AttendanceRecord::with(['employee', 'payrollPeriod'])->get(),
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
