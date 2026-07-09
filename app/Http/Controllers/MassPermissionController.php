<?php

namespace App\Http\Controllers;

use App\Enums\AttendancePermissionStatus;
use App\Enums\MassPermissionStatus;
use App\Models\AttendancePermission;
use App\Models\ClosedDay;
use App\Models\Employee;
use App\Models\MassPermission;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Manages Mass Permission requests — batch excused-absence declarations covering
 * all active employees for declared closed days within a payroll period.
 *
 * Two-layer approval:
 *   1. Initiated (draft) + submitted (pending_approval) — by any `create mass permission` holder.
 *   2. First Approval (pending_confirmation)            — by any `approve mass permission` holder.
 *   3. Final Confirmation (approved)                   — by a DIFFERENT `approve mass permission` holder.
 *      ↳ spawns individual AttendancePermission rows for each active employee.
 */
class MassPermissionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Finance/MassPermissions/Index', self::pageProps($request->user()));
    }

    /** Shared payload for the admin panel and ModuleController. */
    public static function pageProps($user): array
    {
        return [
            'massPermissions' => MassPermission::with([
                'payrollPeriod:id,name',
                'initiatedBy:id,name',
                'firstApprovedBy:id,name',
                'finalApprovedBy:id,name',
                'closedDays:id,start_date,end_date,name,type',
            ])
                ->latest()
                ->get()
                ->map(fn ($mp) => [
                    'id'                  => $mp->id,
                    'name'                => $mp->name,
                    'reason'              => $mp->reason,
                    'period'              => $mp->payrollPeriod?->name,
                    'status'              => $mp->status->value,
                    'status_label'        => $mp->status->label(),
                    'total_days'          => $mp->total_days,
                    'initiated_by'        => $mp->initiatedBy?->name,
                    'submitted_at'        => $mp->submitted_at?->toDateTimeString(),
                    'first_approved_by'   => $mp->firstApprovedBy?->name,
                    'first_approved_at'   => $mp->first_approved_at?->toDateTimeString(),
                    'first_review_notes'  => $mp->first_review_notes,
                    'final_approved_by'   => $mp->finalApprovedBy?->name,
                    'final_approved_at'   => $mp->final_approved_at?->toDateTimeString(),
                    'final_review_notes'  => $mp->final_review_notes,
                    'employees_affected'  => $mp->employees_affected,
                    'permissions_spawned' => $mp->permissions_spawned,
                    'closed_days'         => $mp->closedDays->map(fn ($d) => [
                        'id'         => $d->id,
                        'start_date' => $d->start_date->toDateString(),
                        'end_date'   => $d->end_date->toDateString(),
                        'name'       => $d->name,
                        'type'       => $d->type->value,
                    ]),
                ]),
            'closedDays' => ClosedDay::where('is_active', true)
                ->orderBy('start_date')
                ->get()
                ->map(fn ($d) => [
                    'id'         => $d->id,
                    'start_date' => $d->start_date->toDateString(),
                    'end_date'   => $d->end_date->toDateString(),
                    'days_count' => $d->days_count,
                    'name'       => $d->name,
                    'type'       => $d->type->value,
                ]),
            'periods' => PayrollPeriod::monthly()->forActiveYear()->orderByDesc('start_date')->get(['id', 'name']),
            'can'     => [
                'create'  => (bool) $user?->can('create mass permission'),
                'approve' => (bool) $user?->can('approve mass permission'),
            ],
            'user_id' => $user?->id,
        ];
    }

    /** Create a mass permission draft. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:200'],
            'reason'            => ['nullable', 'string', 'max:1000'],
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'closed_day_ids'    => ['required', 'array', 'min:1'],
            'closed_day_ids.*'  => ['exists:closed_days,id'],
        ]);

        // Total excused days = distinct calendar days across every selected
        // closed-day range (ranges may overlap, so de-duplicate the dates).
        $dayCount = ClosedDay::whereIn('id', $data['closed_day_ids'])
            ->get()
            ->flatMap(fn ($cd) => $cd->dates())
            ->unique()
            ->count();

        $mp = MassPermission::create([
            'name'              => $data['name'],
            'reason'            => $data['reason'] ?? null,
            'payroll_period_id' => $data['payroll_period_id'],
            'status'            => MassPermissionStatus::Draft,
            'total_days'        => $dayCount,
            'initiated_by'      => $request->user()->id,
        ]);

        $mp->closedDays()->sync($data['closed_day_ids']);

        return back()->with('success', 'Mass permission draft created. Submit it to begin the approval workflow.');
    }

    /** Initiator submits the draft to start the approval chain. */
    public function submit(Request $request, MassPermission $massPermission)
    {
        if (! $massPermission->canBeSubmitted()) {
            return back()->with('error', 'Only a draft mass permission can be submitted.');
        }

        $massPermission->update([
            'status'       => MassPermissionStatus::PendingApproval,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Mass permission submitted — awaiting first approval.');
    }

    /** First approval by a President / Super Admin. Moves to pending_confirmation. */
    public function firstApprove(Request $request, MassPermission $massPermission)
    {
        if (! $massPermission->canBeFirstApproved()) {
            return back()->with('error', 'This mass permission is not awaiting first approval.');
        }

        $massPermission->update([
            'status'            => MassPermissionStatus::PendingConfirmation,
            'first_approved_by' => $request->user()->id,
            'first_approved_at' => now(),
            'first_review_notes'=> null,
        ]);

        return back()->with('success', 'First approval recorded — awaiting final confirmation from a second authoriser.');
    }

    /**
     * Final confirmation by a DIFFERENT President / Super Admin.
     * Spawns one AttendancePermission per active employee.
     */
    public function finalApprove(Request $request, MassPermission $massPermission)
    {
        if (! $massPermission->canBeFinalApproved()) {
            return back()->with('error', 'This mass permission is not awaiting final confirmation.');
        }

        // Two-person rule: final confirmer must differ from first approver.
        if ((int) $massPermission->first_approved_by === $request->user()->id) {
            return back()->with('error', 'The final confirmation must be done by a different authoriser than the first approver.');
        }

        DB::transaction(function () use ($massPermission, $request) {
            $employees = Employee::where('is_active', true)->get(['id']);
            $count     = $employees->count();

            // Expand every attached closed-day range into distinct calendar
            // dates, then take the span for the spawned permissions.
            $dates    = $massPermission->closedDays
                ->flatMap(fn ($cd) => $cd->dates())
                ->unique()
                ->sort()
                ->values();
            $firstDate = $dates->first();
            $lastDate  = $dates->last();
            $dateStr   = $dates->count() === 1
                ? $firstDate
                : $firstDate.' – '.$lastDate;

            $reason = $massPermission->name.
                ' (mass permission — '.$massPermission->total_days.' day(s) on '.$dateStr.')';

            foreach ($employees as $employee) {
                // Upsert: if a mass permission for the same period + employee already
                // exists from a previous run, update it rather than duplicate.
                AttendancePermission::updateOrCreate(
                    [
                        'employee_id'        => $employee->id,
                        'payroll_period_id'  => $massPermission->payroll_period_id,
                        'mass_permission_id' => $massPermission->id,
                    ],
                    [
                        'start_date'  => $firstDate,
                        'end_date'    => $lastDate,
                        'days'        => $massPermission->total_days,
                        'reason'      => $reason,
                        'status'      => AttendancePermissionStatus::Approved,
                        'created_by'  => $massPermission->initiated_by,
                        'approved_by' => $request->user()->id,
                        'approved_at' => now(),
                    ]
                );
            }

            $massPermission->update([
                'status'              => MassPermissionStatus::Approved,
                'final_approved_by'   => $request->user()->id,
                'final_approved_at'   => now(),
                'final_review_notes'  => null,
                'employees_affected'  => $count,
                'permissions_spawned' => true,
            ]);
        });

        return back()->with('success',
            "Mass permission fully approved — {$massPermission->employees_affected} attendance permissions auto-created for the next payroll run."
        );
    }

    /** Reject at any pending stage (review notes required). */
    public function reject(Request $request, MassPermission $massPermission)
    {
        if (! $massPermission->canBeRejected()) {
            return back()->with('error', 'This mass permission cannot be rejected at its current stage.');
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Store rejection notes in the appropriate layer's field.
        $notesField = $massPermission->isPendingConfirmation()
            ? 'final_review_notes'
            : 'first_review_notes';

        $massPermission->update([
            'status'   => MassPermissionStatus::Rejected,
            $notesField => $data['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Mass permission rejected.');
    }
}
