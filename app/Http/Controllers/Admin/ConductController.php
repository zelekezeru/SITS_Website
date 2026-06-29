<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConductIssue;
use App\Models\ConductDecision;
use App\Models\Employee;
use App\Models\EmployeeStatusChange;
use App\Enums\ConductStatus;
use App\Enums\ConductDecision as ConductDecisionEnum;
use App\Enums\EmployeeStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ConductController extends Controller
{
    /**
     * Display a listing of conduct issues.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ConductIssue::class);

        $query = ConductIssue::with('employee.user', 'createdBy', 'approvedBy', 'decision');

        // Department heads see only their own department's issues.
        $user = auth()->user();
        if ($user->can('manage department conduct') && ! $user->can('manage conduct issues')) {
            $departmentIds = $user->managedDepartmentIds();
            $query->whereHas('employee', fn ($q) => $q->whereIn('department_id', $departmentIds));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Search by employee name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $issues = $query->latest()->paginate(15);

        return Inertia::render('Admin/Conduct/ConductIssueList', [
            'issues' => $issues,
            'statuses' => ConductStatus::cases(),
            'severities' => \App\Enums\ConductSeverity::cases(),
            'filters' => $request->only('status', 'severity', 'search'),
        ]);
    }

    /**
     * Show the form for creating a new conduct issue.
     */
    public function create()
    {
        Gate::authorize('create', ConductIssue::class);

        // Get employees for selection
        $employees = Employee::with('user', 'department')
            ->where('is_active', true)
            ->get()
            ->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->user->name,
                'department' => $emp->department->name_en ?? 'N/A',
            ]);

        return Inertia::render('Admin/Conduct/ConductIssueForm', [
            'employees' => $employees,
            'issueTypes' => \App\Enums\ConductIssueType::cases(),
            'severities' => \App\Enums\ConductSeverity::cases(),
        ]);
    }

    /**
     * Store a newly created conduct issue in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', ConductIssue::class);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'issue_type' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, \App\Enums\ConductIssueType::cases())),
            'severity' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, \App\Enums\ConductSeverity::cases())),
            'description_en' => 'required|string|max:2000',
            'description_am' => 'nullable|string|max:2000',
            'justification' => 'nullable|string|max:1000',
            'incident_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'witnesses' => 'nullable|array',
            'witnesses.*' => 'string|email',
        ]);

        $issue = ConductIssue::create([
            ...$validated,
            'status' => ConductStatus::Draft,
        ]);

        return redirect()->route('admin.conduct.show', $issue)
            ->with('success', 'Conduct issue created successfully.');
    }

    /**
     * Display the specified conduct issue.
     */
    public function show(ConductIssue $issue)
    {
        Gate::authorize('view', $issue);

        return Inertia::render('Admin/Conduct/ConductIssueDetail', [
            'issue' => $issue->load('employee.user', 'employee.department', 'createdBy', 'approvedBy', 'decision'),
            'canApprove' => auth()->user()->can('approve', $issue),
            'canReject' => auth()->user()->can('reject', $issue),
            'canDecide' => auth()->user()->can('create', ConductDecision::class),
        ]);
    }

    /**
     * Show the form for editing the specified conduct issue.
     */
    public function edit(ConductIssue $issue)
    {
        Gate::authorize('update', $issue);

        return Inertia::render('Admin/Conduct/ConductIssueForm', [
            'issue' => $issue,
            'issueTypes' => \App\Enums\ConductIssueType::cases(),
            'severities' => \App\Enums\ConductSeverity::cases(),
        ]);
    }

    /**
     * Update the specified conduct issue in storage.
     */
    public function update(Request $request, ConductIssue $issue)
    {
        Gate::authorize('update', $issue);

        $validated = $request->validate([
            'issue_type' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, \App\Enums\ConductIssueType::cases())),
            'severity' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, \App\Enums\ConductSeverity::cases())),
            'description_en' => 'required|string|max:2000',
            'description_am' => 'nullable|string|max:2000',
            'justification' => 'nullable|string|max:1000',
            'incident_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'witnesses' => 'nullable|array',
            'witnesses.*' => 'string|email',
        ]);

        $issue->update($validated);

        return redirect()->route('admin.conduct.show', $issue)
            ->with('success', 'Conduct issue updated successfully.');
    }

    /**
     * Submit a conduct issue for approval.
     */
    public function submit(Request $request, ConductIssue $issue)
    {
        Gate::authorize('submit', $issue);

        if ($issue->submit()) {
            return redirect()->back()->with('success', 'Conduct issue submitted for review.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot submit this issue.']);
    }

    /**
     * Approve a conduct issue.
     */
    public function approve(Request $request, ConductIssue $issue)
    {
        Gate::authorize('approve', $issue);

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if ($issue->approve($validated['approval_notes'] ?? null)) {
            return redirect()->back()->with('success', 'Conduct issue approved.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot approve this issue.']);
    }

    /**
     * Reject a conduct issue.
     */
    public function reject(Request $request, ConductIssue $issue)
    {
        Gate::authorize('reject', $issue);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($issue->reject($validated['rejection_reason'])) {
            return redirect()->back()->with('success', 'Conduct issue rejected.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot reject this issue.']);
    }

    /**
     * Delete a conduct issue.
     */
    public function destroy(ConductIssue $issue)
    {
        Gate::authorize('delete', $issue);

        $issue->delete();

        return redirect()->route('admin.conduct.index')
            ->with('success', 'Conduct issue deleted successfully.');
    }

    // ============================================
    // CONDUCT DECISIONS
    // ============================================

    /**
     * Create a conduct decision for an issue.
     */
    public function createDecision(Request $request, ConductIssue $issue)
    {
        Gate::authorize('create', ConductDecision::class);

        return Inertia::render('Admin/Conduct/ConductDecisionForm', [
            'issue' => $issue->load('employee.user'),
            'decisions' => ConductDecisionEnum::cases(),
        ]);
    }

    /**
     * Store a conduct decision.
     */
    public function storeDecision(Request $request, ConductIssue $issue)
    {
        Gate::authorize('create', ConductDecision::class);

        // A decision is only meaningful once the issue has cleared maker-checker.
        abort_unless($issue->isApproved(), 422, 'A decision can only be recorded on an approved conduct issue.');

        $validated = $request->validate([
            'decision' => 'required|in:' . implode(',', array_map(fn ($e) => $e->value, ConductDecisionEnum::cases())),
            'decision_notes_en' => 'nullable|string|max:2000',
            'decision_notes_am' => 'nullable|string|max:2000',
            'effective_date' => 'required|date',
            'expires_at' => 'nullable|date|after:effective_date',
        ]);

        DB::transaction(function () use ($issue, $validated) {
            $decision = ConductDecision::create([
                'conduct_issue_id' => $issue->id,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
                'status' => 'active',
                ...$validated,
            ]);

            $issue->update(['status' => ConductStatus::Resolved]);

            // Disciplinary decisions (suspension / termination / dismissal) move
            // the employee's canonical status and leave an auditable trail.
            if ($decision->requiresEmployeeStatusChange()) {
                $employee = $issue->employee;
                $target = EmployeeStatus::from($decision->getTargetEmployeeStatus());
                $from = $employee->status ?? EmployeeStatus::Active;

                $employee->update([
                    'status' => $target,
                    'is_active' => ! $target->isFinal(),
                ]);

                EmployeeStatusChange::create([
                    'employee_id' => $employee->id,
                    'from_status' => $from,
                    'to_status' => $target,
                    'reason' => 'conduct_decision',
                    'notes' => 'Conduct decision: ' . $decision->decision->label(),
                    'changed_by' => auth()->id(),
                    'effective_date' => $decision->effective_date,
                    'changed_at' => now(),
                    'reference_type' => 'conduct_decision',
                    'reference_id' => $decision->id,
                ]);
            }
        });

        return redirect()->route('admin.conduct.show', $issue)
            ->with('success', 'Conduct decision recorded successfully.');
    }

    /**
     * Show a conduct decision.
     */
    public function showDecision(ConductDecision $decision)
    {
        Gate::authorize('view', $decision);

        return Inertia::render('Admin/Conduct/ConductDecisionDetail', [
            'decision' => $decision->load('conductIssue.employee.user', 'decidedBy'),
            'canAppeal' => auth()->user()->can('appeal', $decision),
            'canOverturn' => auth()->user()->can('overturn', $decision),
        ]);
    }

    /**
     * Appeal a conduct decision.
     */
    public function appeal(Request $request, ConductDecision $decision)
    {
        Gate::authorize('appeal', $decision);

        $validated = $request->validate([
            'appeal_notes' => 'required|string|max:2000',
        ]);

        if ($decision->appeal($validated['appeal_notes'])) {
            return redirect()->back()->with('success', 'Appeal submitted successfully.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot appeal this decision.']);
    }

    /**
     * Overturn a conduct decision.
     */
    public function overturn(Request $request, ConductDecision $decision)
    {
        Gate::authorize('overturn', $decision);

        $validated = $request->validate([
            'overturn_reason' => 'required|string|max:2000',
        ]);

        if ($decision->overturn($validated['overturn_reason'])) {
            return redirect()->back()->with('success', 'Decision overturned successfully.');
        }

        return redirect()->back()->withErrors(['error' => 'Cannot overturn this decision.']);
    }
}
