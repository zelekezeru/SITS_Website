<?php

namespace App\Http\Controllers;

use App\Enums\AttendancePermissionStatus;
use App\Models\AttendancePermission;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Excused-absence ("permission") requests. Created by the Admin or Operations
 * Manager and approved by the Admin before payroll calculation. On approval the
 * days roll into the employee's permitted_days at run time. Both initiator and
 * approver are stamped on every request.
 */
class AttendancePermissionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Finance/AttendancePermissions/Index', self::pageProps($request->user()));
    }

    /** Shared payload for the admin (ModuleController) and Finance/Ops views. */
    public static function pageProps($user): array
    {
        return [
            'permissions' => AttendancePermission::with(['employee:id,full_name_en', 'payrollPeriod:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
                ->latest()
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'employee' => $p->employee?->full_name_en,
                    'period' => $p->payrollPeriod?->name,
                    'start_date' => $p->start_date?->toDateString(),
                    'end_date' => $p->end_date?->toDateString(),
                    'days' => $p->days,
                    'reason' => $p->reason,
                    'file_path' => $p->file_path ? route('attendance-permissions.file', $p) : null,
                    'status' => $p->status->value,
                    'status_label' => $p->status->label(),
                    'created_by' => $p->createdBy?->name,
                    'approved_by' => $p->approvedBy?->name,
                    'approved_at' => $p->approved_at?->toDateTimeString(),
                    'review_notes' => $p->review_notes,
                ]),
            'employees' => Employee::where('is_active', true)->orderBy('full_name_en')->get(['id', 'full_name_en']),
            'periods' => PayrollPeriod::monthly()->forActiveYear()->orderByDesc('start_date')->get(['id', 'name']),
            'can' => [
                'create' => (bool) $user?->can('create attendance permission'),
                'approve' => (bool) $user?->can('approve attendance permission'),
            ],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'integer', 'min:1', 'max:31'],
            'reason' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        // Store on the private 'local' disk (storage/app) — never web-served.
        // Access is mediated by the authorised download route below.
        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('attendance-permissions', 'local')
            : null;

        AttendancePermission::create(array_merge(
            collect($data)->except('file')->toArray(),
            [
                'file_path' => $filePath,
                'status' => AttendancePermissionStatus::Pending,
                'created_by' => $request->user()->id,
            ]
        ));

        return back()->with('success', 'Attendance permission submitted for approval.');
    }

    /** Stream a permission's supporting document to authorised users only. */
    public function downloadFile(Request $request, AttendancePermission $permission)
    {
        abort_unless($permission->file_path, 404);

        $user = $request->user();
        abort_unless(
            $user->can('create attendance permission') || $user->can('approve attendance permission'),
            403
        );

        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($permission->file_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($permission->file_path);
    }

    public function approve(Request $request, AttendancePermission $permission)
    {
        if (! $permission->isPending()) {
            return back()->with('error', 'Only a pending permission can be approved.');
        }

        $permission->update([
            'status' => AttendancePermissionStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'review_notes' => null,
        ]);

        return back()->with('success', 'Permission approved — its days will count at the next payroll run.');
    }

    public function reject(Request $request, AttendancePermission $permission)
    {
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        if (! $permission->isPending()) {
            return back()->with('error', 'Only a pending permission can be rejected.');
        }

        $permission->update([
            'status' => AttendancePermissionStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Permission rejected.');
    }
}
