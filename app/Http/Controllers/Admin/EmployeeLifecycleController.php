<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Termination;
use App\Models\EmployeeStatusChange;
use App\Enums\LeaveStatus;
use App\Enums\TerminationReason;
use App\Enums\EmployeeStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EmployeeLifecycleController extends Controller
{
    // ========================================
    // LEAVE REQUEST MANAGEMENT
    // ========================================

    public function leaveIndex(Request $request)
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $query = LeaveRequest::with('employee.user', 'employee.department', 'approvedBy', 'rejectedBy');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by leave type
        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->input('leave_type'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $leaves = $query->latest()->paginate(15);

        return Inertia::render('Admin/EmployeeLifecycle/LeaveRequestList', [
            'leaves' => $leaves,
            'statuses' => LeaveStatus::cases(),
            'leaveTypes' => \App\Enums\LeaveType::cases(),
            'filters' => $request->only('status', 'leave_type', 'search'),
        ]);
    }

    public function leaveCreate()
    {
        Gate::authorize('create', LeaveRequest::class);

        $employees = Employee::with('user', 'department')
            ->where('is_active', true)
            ->get()
            ->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->user->name,
                'department' => $emp->department->name_en ?? 'N/A',
            ]);

        return Inertia::render('Admin/EmployeeLifecycle/LeaveRequestForm', [
            'employees' => $employees,
            'leaveTypes' => \App\Enums\LeaveType::cases(),
        ]);
    }

    public function leaveStore(Request $request)
    {
        Gate::authorize('create', LeaveRequest::class);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, \App\Enums\LeaveType::cases())),
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        $daysRequested = now()->parse($validated['start_date'])->diffInDays(now()->parse($validated['end_date'])) + 1;

        $leave = LeaveRequest::create([
            ...$validated,
            'days_requested' => $daysRequested,
            'status' => LeaveStatus::Submitted,
        ]); // created_by stamped by Blameable

        return redirect()->route('admin.leave.show', $leave)
            ->with('success', 'Leave request created successfully.');
    }

    public function leaveShow(LeaveRequest $leave)
    {
        Gate::authorize('view', $leave);

        return Inertia::render('Admin/EmployeeLifecycle/LeaveRequestDetail', [
            'leave' => $leave->load('employee.user', 'employee.department', 'approvedBy', 'rejectedBy', 'documents'),
            'canApprove' => auth()->user()->can('approve', $leave),
            'canReject' => auth()->user()->can('reject', $leave),
            'canCancel' => auth()->user()->can('cancel', $leave),
        ]);
    }

    public function leaveApprove(Request $request, LeaveRequest $leave)
    {
        Gate::authorize('approve', $leave);

        $validated = $request->validate([
            'days_approved' => 'required|integer|min:1|max:' . $leave->days_requested,
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if ($leave->approve($validated['days_approved'], $validated['approval_notes'] ?? null)) {
            return redirect()->back()->with('success', 'Leave request approved.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot approve this leave request.']);
    }

    public function leaveReject(Request $request, LeaveRequest $leave)
    {
        Gate::authorize('reject', $leave);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($leave->reject($validated['rejection_reason'])) {
            return redirect()->back()->with('success', 'Leave request rejected.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot reject this leave request.']);
    }

    public function leaveCancel(Request $request, LeaveRequest $leave)
    {
        Gate::authorize('cancel', $leave);

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        if ($leave->cancel($validated['cancellation_reason'])) {
            return redirect()->back()->with('success', 'Leave request cancelled.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot cancel this leave request.']);
    }

    // ========================================
    // TERMINATION MANAGEMENT
    // ========================================

    public function terminationIndex(Request $request)
    {
        Gate::authorize('viewAny', Termination::class);

        $query = Termination::with('employee.user', 'employee.department', 'initiatedBy', 'finalizedBy');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->input('reason'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $terminations = $query->latest()->paginate(15);

        return Inertia::render('Admin/EmployeeLifecycle/TerminationList', [
            'terminations' => $terminations,
            'reasons' => TerminationReason::cases(),
            'filters' => $request->only('status', 'reason', 'search'),
        ]);
    }

    public function terminationCreate()
    {
        Gate::authorize('create', Termination::class);

        $employees = Employee::with('user', 'department')
            ->where('is_active', true)
            ->get()
            ->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->user->name,
                'department' => $emp->department->name_en ?? 'N/A',
            ]);

        return Inertia::render('Admin/EmployeeLifecycle/TerminationForm', [
            'employees' => $employees,
            'reasons' => TerminationReason::cases(),
        ]);
    }

    public function terminationStore(Request $request)
    {
        Gate::authorize('create', Termination::class);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'reason' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, TerminationReason::cases())),
            'effective_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:2000',
            'severance_amount' => 'nullable|numeric|min:0',
        ]);

        $termination = Termination::create([
            ...$validated,
            'status' => 'pending',
            'initiated_by' => auth()->id(),
            'initiated_at' => now(),
        ]);

        return redirect()->route('admin.terminations.show', $termination)
            ->with('success', 'Termination record created.');
    }

    public function terminationShow(Termination $termination)
    {
        Gate::authorize('view', $termination);

        return Inertia::render('Admin/EmployeeLifecycle/TerminationDetail', [
            'termination' => $termination->load('employee.user', 'employee.department', 'initiatedBy', 'finalizedBy', 'finalPayslip', 'documents'),
            'canFinalize' => auth()->user()->can('finalize', $termination),
        ]);
    }

    public function terminationFinalize(Request $request, Termination $termination)
    {
        Gate::authorize('finalize', $termination);

        $validated = $request->validate([
            'severance_amount' => 'nullable|numeric|min:0',
            'severance_notes' => 'nullable|string|max:1000',
            'handover_tasks' => 'nullable|boolean',
            'handover_equipment' => 'nullable|boolean',
            'handover_documents' => 'nullable|boolean',
        ]);

        $checklist = [
            'tasks' => $validated['handover_tasks'] ?? false,
            'equipment' => $validated['handover_equipment'] ?? false,
            'documents' => $validated['handover_documents'] ?? false,
        ];

        if ($termination->finalize($checklist)) {
            $employee = $termination->employee;
            $fromStatus = $employee->status ?? EmployeeStatus::Active;

            $employee->update([
                'status' => EmployeeStatus::Terminated,
                'is_active' => false,
            ]);

            EmployeeStatusChange::create([
                'employee_id' => $employee->id,
                'from_status' => $fromStatus,
                'to_status' => EmployeeStatus::Terminated,
                'reason' => 'termination',
                'notes' => 'Employment terminated: ' . $termination->reason->label(),
                'changed_by' => auth()->id(),
                'effective_date' => $termination->effective_date,
                'changed_at' => now(),
                'reference_type' => 'termination',
                'reference_id' => $termination->id,
            ]);

            return redirect()->back()->with('success', 'Termination finalized and employee status updated.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot finalize this termination.']);
    }

    // ========================================
    // EMPLOYEE STATUS CHANGES
    // ========================================

    public function statusChangeIndex(Request $request)
    {
        Gate::authorize('viewAny', EmployeeStatusChange::class);

        $query = EmployeeStatusChange::with('employee.user', 'changedBy');

        if ($request->filled('from_status')) {
            $query->where('from_status', $request->input('from_status'));
        }

        if ($request->filled('to_status')) {
            $query->where('to_status', $request->input('to_status'));
        }

        $changes = $query->latest()->paginate(20);

        return Inertia::render('Admin/EmployeeLifecycle/StatusChangeLog', [
            'changes' => $changes,
            'statuses' => EmployeeStatus::cases(),
            'filters' => $request->only('from_status', 'to_status'),
        ]);
    }

    public function statusChangeShow(EmployeeStatusChange $change)
    {
        Gate::authorize('view', $change);

        return Inertia::render('Admin/EmployeeLifecycle/StatusChangeDetail', [
            'change' => $change->load('employee.user', 'changedBy'),
        ]);
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        Gate::authorize('manage employees');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,on_leave,suspended,terminated,inactive'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $fromStatus = $employee->status ?? EmployeeStatus::Active;
        $toStatus = EmployeeStatus::from($validated['status']);

        // Update employee
        $employee->update([
            'status' => $toStatus,
            'is_active' => in_array($validated['status'], ['active', 'on_leave'], true),
        ]);

        // Create log entry
        EmployeeStatusChange::create([
            'employee_id' => $employee->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'changed_by' => auth()->id(),
            'effective_date' => $validated['effective_date'] ? \Carbon\Carbon::parse($validated['effective_date']) : now(),
            'changed_at' => now(),
        ]);

        return redirect()->back()->with('success', "Employee status updated successfully.");
    }
}
