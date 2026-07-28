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
        $permissions = AttendancePermission::with(['employee:id,full_name_en,staff_no', 'payrollPeriod:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'employee_id' => $p->employee_id,
                'employee' => $p->employee?->full_name_en,
                'staff_no' => $p->employee?->staff_no,
                'payroll_period_id' => $p->payroll_period_id,
                'period' => $p->payrollPeriod?->name,
                'start_date' => $p->start_date?->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
                'days' => $p->days,
                'reason' => $p->reason,
                'file_path' => $p->file_path ? route('attendance-permissions.file', $p) : null,
                // Drives the "no supporting document" indicator and its filter.
                'has_evidence' => (bool) $p->file_path,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'created_by' => $p->createdBy?->name,
                'approved_by' => $p->approvedBy?->name,
                'approved_at' => $p->approved_at?->toDateTimeString(),
                'review_notes' => $p->review_notes,
                // Batch-generated entries are owned by their mass permission; only
                // the evidence may be attached to them after the fact.
                'is_mass' => $p->mass_permission_id !== null,
                'can_edit' => $p->isPending() && $p->mass_permission_id === null,
                // Evidence can always arrive late — including after approval.
                'can_attach' => ! $p->isRejected(),
            ]);

        // Periods carry their own counts so the tabs can be labelled without the
        // client re-deriving them, and periods with no permissions still appear.
        $periods = PayrollPeriod::monthly()->forActiveYear()
            ->orderByDesc('start_date')->get(['id', 'name'])
            ->map(function ($period) use ($permissions) {
                $forPeriod = $permissions->where('payroll_period_id', $period->id);

                return [
                    'id' => $period->id,
                    'name' => $period->name,
                    'total' => $forPeriod->count(),
                    'pending' => $forPeriod->where('status', 'pending')->count(),
                    'missing_evidence' => $forPeriod->where('has_evidence', false)->count(),
                    'approved_days' => $forPeriod->where('status', 'approved')->sum('days'),
                ];
            });

        return [
            'permissions' => $permissions->values(),
            'employees' => Employee::where('is_active', true)->orderBy('full_name_en')->get(['id', 'full_name_en']),
            'periods' => $periods,
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

    /**
     * Refine a pending request. The attachment is only touched when a new file is
     * sent (or removal is explicitly asked for), so editing the dates never
     * silently drops evidence already on file.
     */
    public function update(Request $request, AttendancePermission $permission)
    {
        if (! $permission->isEditable()) {
            return back()->with('error', $permission->isPending()
                ? 'Batch permissions are edited on the mass permission they came from.'
                : 'Only a pending permission can be edited.');
        }

        $data = $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'integer', 'min:1', 'max:31'],
            'reason' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240'],
            'remove_file' => ['nullable', 'boolean'],
        ]);

        $attributes = collect($data)->except(['file', 'remove_file'])->toArray();

        if ($request->hasFile('file')) {
            $this->deleteStoredFile($permission);
            $attributes['file_path'] = $request->file('file')->store('attendance-permissions', 'local');
        } elseif ($request->boolean('remove_file')) {
            $this->deleteStoredFile($permission);
            $attributes['file_path'] = null;
        }

        $permission->update($attributes);

        return back()->with('success', 'Permission updated.');
    }

    /**
     * Attach (or replace) the supporting document on an existing permission.
     *
     * Evidence routinely arrives after the request itself — a medical note handed
     * in days later — so this stays open after approval too. Only a rejected
     * request is closed to it.
     */
    public function attachFile(Request $request, AttendancePermission $permission)
    {
        if ($permission->isRejected()) {
            return back()->with('error', 'A rejected permission cannot take an attachment.');
        }

        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $this->deleteStoredFile($permission);

        $permission->update([
            'file_path' => $request->file('file')->store('attendance-permissions', 'local'),
        ]);

        return back()->with('success', 'Supporting document attached.');
    }

    /** Remove the stored file for a permission, if any, from the private disk. */
    private function deleteStoredFile(AttendancePermission $permission): void
    {
        if ($permission->file_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($permission->file_path);
        }
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
