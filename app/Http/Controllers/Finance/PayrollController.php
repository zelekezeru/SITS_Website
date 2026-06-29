<?php

namespace App\Http\Controllers\Finance;

use App\Enums\PayrollStatus;
use App\Enums\ScheduleType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Payroll\PayrollRunService;
use App\Services\Payroll\PayrollSheetExport;
use App\Support\PayrollSheetPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        $periods = PayrollPeriod::monthly()->forActiveYear()
            ->withCount('payslips')
            ->withSum('payslips as net_total', 'net_pay')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
                'start_date' => $p->start_date?->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
                'payment_date' => $p->payment_date?->toDateString(),
                'payslips' => $p->payslips_count,
                'netTotal' => (float) $p->net_total,
            ]);

        return Inertia::render('Finance/Payroll/Index', ['periods' => $periods]);
    }

    public function show(PayrollPeriod $period, Request $request)
    {
        return Inertia::render('Finance/Payroll/ShowPeriod', self::periodProps($period, $request->user(), isAdmin: false));
    }

    public function run(PayrollPeriod $period, Request $request, PayrollRunService $runService)
    {
        if (! $period->canBeEditedByFinance()) {
            return back()->with('error', 'This period can no longer be prepared by Finance.');
        }

        if (! Employee::where('is_active', true)->exists()) {
            return back()->with('error', 'There are no active employees to process.');
        }

        $count = $runService->run($period, $request->user());

        return back()->with('success', "Payroll prepared for {$count} active employee(s).");
    }

    public function submit(PayrollPeriod $period)
    {
        if (! $period->canBeEditedByFinance()) {
            return back()->with('error', 'Only an open, in-progress or returned period can be submitted.');
        }

        if ($period->payslips()->count() === 0) {
            return back()->with('error', 'Prepare the payroll before submitting it for approval.');
        }

        $period->update([
            'status' => PayrollStatus::PendingApproval,
            'submitted_at' => now(),
            'review_notes' => null,
        ]);

        return back()->with('success', "{$period->name} submitted to the President for approval.");
    }

    public function storeAssignment(PayrollPeriod $period, Request $request)
    {
        if (! $period->canBeEditedByFinance()) {
            return back()->with('error', 'Assignments cannot be changed once a period is submitted or locked.');
        }

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_component_id' => ['required', 'exists:payroll_components,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'schedule_type' => ['required', Rule::in(array_column(ScheduleType::cases(), 'value'))],
            'start_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'end_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // One-time defaults to this period; recurring defaults its start here too.
        if (empty($data['start_period_id']) && $data['schedule_type'] !== ScheduleType::Monthly->value) {
            $data['start_period_id'] = $period->id;
        }
        if ($data['schedule_type'] === ScheduleType::OneTime->value) {
            $data['end_period_id'] = $data['start_period_id'];
        }

        // Only allowance/deduction components are assignable; statutory apply globally.
        $component = PayrollComponent::findOrFail($data['payroll_component_id']);
        abort_if($component->kind === \App\Enums\PayrollComponentKind::Statutory, 422, 'Statutory components apply globally and are not assigned per employee.');

        PayrollComponentAssignment::create($data);

        return back()->with('success', 'Component assigned. Recompute to apply it to the payslip.');
    }

    public function destroyAssignment(PayrollPeriod $period, PayrollComponentAssignment $assignment)
    {
        if (! $period->canBeEditedByFinance()) {
            return back()->with('error', 'Assignments cannot be changed once a period is submitted or locked.');
        }

        $assignment->delete();

        return back()->with('success', 'Assignment removed. Recompute to apply the change.');
    }

    public function exportExcel(PayrollPeriod $period, Request $request, PayrollSheetExport $export)
    {
        $rows = PayrollSheetPresenter::rows($period, $this->scopeIds($request));

        return $export->download($period, $rows);
    }

    public function exportPdf(PayrollPeriod $period, Request $request)
    {
        $rows = PayrollSheetPresenter::rows($period, $this->scopeIds($request));
        $pdf = Pdf::loadView('pdf.payroll-period', [
            'period' => $period,
            'rows' => $rows,
            'totals' => PayrollSheetPresenter::totals($rows),
            'columns' => PayrollSheetPresenter::COLUMNS,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('payroll_'.str_replace(' ', '_', $period->name).'.pdf');
    }

    /** @return array<int> employee ids when scope=selected/individual, else [] (all) */
    private function scopeIds(Request $request): array
    {
        $ids = $request->query('ids');
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        return in_array($request->query('scope'), ['selected', 'individual'], true) && $ids
            ? array_map('intval', (array) $ids)
            : [];
    }

    /**
     * Build the shared ShowPeriod page payload for both the Finance and the
     * President views; capabilities are resolved from the user's permissions.
     *
     * @return array<string, mixed>
     */
    public static function periodProps(PayrollPeriod $period, User $user, bool $isAdmin): array
    {
        $rows = PayrollSheetPresenter::rows($period);
        $hasPayslips = count($rows) > 0;

        $canPrepare = ! $isAdmin && $user->can('prepare payroll') && $period->canBeEditedByFinance();

        return [
            'isAdmin' => $isAdmin,
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'status' => $period->status->value,
                'statusLabel' => $period->status->label(),
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
                'payment_date' => $period->payment_date?->toDateString(),
                'review_notes' => $period->review_notes,
                'prepared_by' => $period->preparedBy?->name,
                'prepared_at' => $period->prepared_at?->toDateTimeString(),
                'submitted_at' => $period->submitted_at?->toDateTimeString(),
                'approved_by' => $period->approvedBy?->name,
                'approved_at' => $period->approved_at?->toDateTimeString(),
            ],
            'rows' => $rows,
            'totals' => PayrollSheetPresenter::totals($rows),
            'columns' => PayrollSheetPresenter::COLUMNS,
            'assignments' => PayrollSheetPresenter::assignmentsByEmployee($period),
            'employees' => Employee::where('is_active', true)
                ->orderBy('full_name_en')
                ->get(['id', 'full_name_en', 'staff_no']),
            'components' => PayrollComponent::active()
                ->whereIn('kind', ['allowance', 'deduction'])
                ->orderBy('kind')->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'kind', 'calc_type', 'rate'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'kind' => $c->kind->value,
                    'is_earning' => $c->kind === \App\Enums\PayrollComponentKind::Allowance,
                    'calc_type' => $c->calc_type->value,
                    'rate' => $c->rate,
                ]),
            'periods' => PayrollPeriod::monthly()->forActiveYear()
                ->orderBy('start_date')->get(['id', 'name']),
            'scheduleTypes' => collect(ScheduleType::cases())
                ->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'can' => [
                'prepare' => $canPrepare,
                'submit' => $canPrepare && $user->can('submit payroll') && $hasPayslips,
                'manageDeductions' => $canPrepare && $user->can('manage deductions'),
                'export' => $hasPayslips && ($isAdmin || $user->can('export payroll')),
                'approve' => $isAdmin && $user->can('approve payroll') && $period->isPendingApproval(),
            ],
        ];
    }
}
