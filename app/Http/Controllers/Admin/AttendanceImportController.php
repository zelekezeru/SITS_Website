<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceImport;
use App\Models\AttendanceImportRow;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Enums\AttendanceRowMatchMethod;
use App\Enums\AttendanceRowMatchStatus;
use App\Services\Attendance\AttendanceImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class AttendanceImportController extends Controller
{
    /**
     * Attendance upload is reachable from both the President admin area and the
     * Finance/Operations portal. The route-name prefix decides which base URL
     * and route names the shared Vue pages should use.
     */
    private function context(): array
    {
        $finance = str_starts_with((string) request()->route()?->getName(), 'finance.');

        return $finance
            ? ['base' => '/finance/attendance-imports', 'prefix' => 'finance.attendance-imports']
            : ['base' => '/admin/attendance-imports', 'prefix' => 'admin.attendance-imports'];
    }

    public function index()
    {
        $ctx = $this->context();

        return Inertia::render('Admin/Finance/AttendanceImports/Index', [
            'imports' => AttendanceImport::with(['payrollPeriod', 'reviewedBy'])->latest()->get(),
            'baseUrl' => $ctx['base'],
        ]);
    }

    public function create()
    {
        // Attendance is imported per month: only the active year's monthly
        // payroll periods (a full calendar month), never the fortnightly ones.
        $periods = PayrollPeriod::monthly()
            ->forActiveYear()
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Admin/Finance/AttendanceImports/Create', [
            'periods' => $periods,
            'baseUrl' => $this->context()['base'],
        ]);
    }

    public function store(Request $request, AttendanceImportService $service)
    {
        $data = $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $period = PayrollPeriod::findOrFail($data['payroll_period_id']);

        if ($period->isLocked()) {
            return redirect()->back()->with('error', 'Cannot import attendance into a locked or paid payroll period.');
        }

        try {
            $import = $service->import($request->file('file'), $period);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route($this->context()['prefix'].'.show', $import)
            ->with('success', 'File imported. Review the matched rows below before approving.');
    }

    public function show(AttendanceImport $attendanceImport, Request $request)
    {
        return Inertia::render('Admin/Finance/AttendanceImports/Show', [
            'attendanceImport' => $attendanceImport->load(['payrollPeriod', 'reviewedBy']),
            'rows' => $attendanceImport->rows()->with('employee')->orderBy('device_name')->get(),
            'employees' => Employee::orderBy('full_name_en')->get(['id', 'full_name_en', 'device_employee_code']),
            'baseUrl' => $this->context()['base'],
            // Approval is the President's step; uploaders only review/resolve rows.
            'canApprove' => (bool) $request->user()?->can('approve attendance'),
        ]);
    }

    public function updateRow(Request $request, AttendanceImport $attendanceImport, AttendanceImportRow $row)
    {
        abort_unless($row->attendance_import_id == $attendanceImport->id, 404);
        abort_unless($attendanceImport->isPendingReview(), 422, 'This import has already been reviewed.');

        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'is_excluded' => ['required', 'boolean'],
            'exclusion_reason' => ['nullable', 'string', 'max:255'],
            'suggested_permitted_days' => ['required', 'integer', 'min:0'],
            'persist_exemption' => ['boolean'],
        ]);

        if (! empty($data['employee_id']) && $data['employee_id'] != $row->employee_id) {
            $employee = Employee::find($data['employee_id']);

            if ($employee && ! $employee->device_employee_code) {
                $employee->update(['device_employee_code' => $row->device_employee_code]);
            }

            $row->employee_id = $data['employee_id'];
            $row->match_status = AttendanceRowMatchStatus::Matched;
            $row->match_method = AttendanceRowMatchMethod::Manual;
            $row->match_confidence = null;
        }

        if (! empty($data['persist_exemption']) && $row->employee_id) {
            Employee::where('id', $row->employee_id)->update([
                'attendance_exempt' => true,
                'attendance_exempt_reason' => $data['exclusion_reason'] ?? null,
            ]);
        }

        $row->is_excluded = $data['is_excluded'];
        $row->exclusion_reason = $data['exclusion_reason'] ?? null;
        $row->suggested_permitted_days = $data['suggested_permitted_days'];
        $row->save();

        $attendanceImport->refresh();
        $attendanceImport->update([
            'matched_count' => $attendanceImport->rows()->where('match_status', 'matched')->count(),
            'ambiguous_count' => $attendanceImport->rows()->where('match_status', 'ambiguous')->count(),
            'unmatched_count' => $attendanceImport->rows()->where('match_status', 'unmatched')->count(),
            'excluded_count' => $attendanceImport->rows()->where('is_excluded', true)->count(),
        ]);

        return redirect()->back()->with('success', 'Row updated.');
    }

    public function approve(Request $request, AttendanceImport $attendanceImport, AttendanceImportService $service)
    {
        if ($attendanceImport->hasUnresolvedRows()) {
            return redirect()->back()->with('error', 'Resolve or exclude every ambiguous/unmatched row before approving.');
        }

        try {
            $result = $service->approve($attendanceImport, $request->user());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with(
            'success',
            "Approved: {$result['created']} attendance record(s) posted, {$result['skipped']} row(s) skipped (excluded/unmatched)."
        );
    }

    public function reject(Request $request, AttendanceImport $attendanceImport, AttendanceImportService $service)
    {
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        try {
            $service->reject($attendanceImport, $request->user(), $data['review_notes'] ?? null);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.attendance-imports')->with('success', 'Import rejected.');
    }

    public function downloadFile(Request $request, AttendanceImport $attendanceImport)
    {
        abort_unless($attendanceImport->file_path, 404);

        $user = $request->user();
        abort_unless(
            $user->can('upload attendance') || $user->can('approve attendance'),
            403
        );

        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($attendanceImport->file_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $attendanceImport->file_path,
            $attendanceImport->original_filename
        );
    }

    public function destroy(Request $request, AttendanceImport $attendanceImport)
    {
        $user = $request->user();
        abort_unless(
            $user->can('upload attendance') || $user->can('approve attendance'),
            403
        );

        if ($attendanceImport->status === \App\Enums\AttendanceImportStatus::Approved) {
            return redirect()->back()->with('error', 'Cannot delete an approved attendance import session.');
        }

        if ($attendanceImport->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($attendanceImport->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($attendanceImport->file_path);
        }

        $attendanceImport->delete();

        return redirect()->route($this->context()['prefix'])->with('success', 'Attendance import session deleted successfully.');
    }
}
