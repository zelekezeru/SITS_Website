<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\TaxBracket;
use App\Models\Employee;
use App\Http\Controllers\Finance\PayrollController as FinancePayrollController;
use App\Services\Payroll\PayrollRunService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class FinanceCrudController extends Controller
{
    // ==========================================
    // PAYROLL PERIODS CRUD
    // ==========================================

    public function storePeriod(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'payment_date' => ['nullable', 'date'],
        ]);

        // Payroll runs on a monthly basis: a period must cover a full calendar month.
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($start->day !== 1 || ! $end->isSameDay($start->copy()->endOfMonth())) {
            return redirect()->back()->with('error', "A payroll period must cover a full calendar month.");
        }

        PayrollPeriod::create($data);

        return redirect()->back()->with('success', "Payroll Period created successfully.");
    }

    public function lockPeriod(PayrollPeriod $period)
    {
        if ($period->status === 'paid') {
            return redirect()->back()->with('error', "A paid period cannot be re-locked.");
        }

        DB::transaction(function () use ($period) {
            $period->update(['status' => 'locked']);
            // Lock cascades so the period becomes truly immutable.
            $period->payslips()->update(['status' => 'locked']);
            $period->attendanceRecords()->update(['status' => 'locked']);
        });

        return redirect()->back()->with('success', "Payroll Period locked — payslips and attendance are now immutable.");
    }

    public function markPeriodPaid(Request $request, PayrollPeriod $period)
    {
        $data = $request->validate([
            'payment_date' => ['nullable', 'date'],
        ]);

        if ($period->status === 'paid') {
            return redirect()->back()->with('error', "This period is already marked as paid.");
        }

        if ($period->payslips()->count() === 0) {
            return redirect()->back()->with('error', "Run payroll before marking the period as paid.");
        }

        DB::transaction(function () use ($period, $data) {
            $period->update([
                'status' => 'paid',
                'payment_date' => $data['payment_date'] ?? $period->payment_date ?? Carbon::today(),
            ]);
            $period->payslips()->update(['status' => 'paid']);
        });

        return redirect()->back()->with('success', "Payroll period marked as paid.");
    }

    /** Collective payroll-period summary for the President (shared with Finance). */
    public function showPeriod(PayrollPeriod $period, Request $request)
    {
        return Inertia::render(
            'Finance/Payroll/ShowPeriod',
            FinancePayrollController::periodProps($period, $request->user(), isAdmin: true)
        );
    }

    /**
     * President/Super Admin approves a Finance-submitted payroll draft.
     * Only a period awaiting approval can be approved.
     */
    public function approvePeriod(Request $request, PayrollPeriod $period)
    {
        if (! $period->isPendingApproval()) {
            return redirect()->back()->with('error', "Only a payroll period awaiting approval can be approved.");
        }

        $period->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'review_notes' => null,
        ]);

        return redirect()->back()->with('success', "Payroll for {$period->name} approved.");
    }

    /** Reject a submitted draft back to Finance with notes. */
    public function rejectPeriod(Request $request, PayrollPeriod $period)
    {
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        if (! $period->isPendingApproval()) {
            return redirect()->back()->with('error', "Only a payroll period awaiting approval can be rejected.");
        }

        $period->update([
            'status' => 'rejected',
            'review_notes' => $data['review_notes'] ?? null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->back()->with('success', "Payroll for {$period->name} sent back to Finance.");
    }

    /**
     * Revert a payroll run back to an editable state so Finance can recompute and
     * resubmit. Allowed only up to "approved" — a locked or paid period is
     * immutable and must stay that way. Payslips are dropped back to draft (kept,
     * not deleted, so nothing is lost) and the submit/approve trail is cleared.
     */
    public function revertPeriod(Request $request, PayrollPeriod $period)
    {
        $revertable = in_array($period->status, [
            PayrollStatus::Processing,
            PayrollStatus::PendingApproval,
            PayrollStatus::Approved,
        ], true);

        if (! $revertable) {
            return redirect()->back()->with('error', 'Only a processing, submitted or approved period can be reverted. Locked and paid periods are immutable.');
        }

        DB::transaction(function () use ($period) {
            $period->update([
                'status' => PayrollStatus::Open,
                'submitted_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'review_notes' => null,
            ]);
            // Keep the computed payslips but return them to draft so Finance can
            // recompute/edit before submitting again.
            $period->payslips()->update(['status' => 'draft']);
        });

        return redirect()->back()->with('success', "{$period->name} reverted to open — Finance can recompute and resubmit for approval.");
    }

    // ==========================================
    // ATTENDANCE EXEMPTIONS
    // ==========================================

    /**
     * Flip an employee's attendance-exempt flag. Exempt staff are skipped by the
     * attendance sync and never incur absence deductions; the reason is kept for
     * audit and cleared when they're returned to tracking.
     */
    public function toggleAttendanceExemption(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'attendance_exempt' => ['required', 'boolean'],
            'attendance_exempt_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $employee->update([
            'attendance_exempt' => $data['attendance_exempt'],
            'attendance_exempt_reason' => $data['attendance_exempt'] ? ($data['attendance_exempt_reason'] ?? null) : null,
        ]);

        $verb = $data['attendance_exempt'] ? 'exempted from' : 'returned to';

        return redirect()->back()->with('success', "{$employee->full_name_en} {$verb} attendance tracking.");
    }

    // ==========================================
    // TAX CONFIGURATION CRUD
    // ==========================================

    public function storeTaxBracket(Request $request)
    {
        $data = $request->validate([
            'min_income' => ['required', 'numeric', 'min:0'],
            'max_income' => ['nullable', 'numeric', 'after:min_income'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'deduction' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
        ]);

        TaxBracket::create($data);

        return redirect()->back()->with('success', "Tax Bracket added successfully.");
    }

    public function updateTaxBracket(Request $request, TaxBracket $bracket)
    {
        $data = $request->validate([
            'min_income' => ['required', 'numeric', 'min:0'],
            'max_income' => ['nullable', 'numeric', 'after:min_income'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'deduction' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
        ]);

        $bracket->update($data);

        return redirect()->back()->with('success', "Tax Bracket updated successfully.");
    }

    // ==========================================
    // ATTENDANCE LOGGING
    // ==========================================

    // ==========================================
    // PAYROLL CALCULATION RUN
    // ==========================================

    public function runPayroll(Request $request, PayrollRunService $runService)
    {
        $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
        ]);

        $period = PayrollPeriod::findOrFail($request->payroll_period_id);

        // Locked OR paid periods are immutable.
        if ($period->isLocked()) {
            return redirect()->back()->with('error', "Cannot run payroll for a locked or paid period.");
        }

        if (! Employee::where('is_active', true)->exists()) {
            return redirect()->back()->with('error', "There are no active employees to process.");
        }

        $count = $runService->run($period, $request->user());

        return redirect()->back()->with('success', "Payroll calculated for {$count} active employee(s).");
    }

    public function payslipPdf(Payslip $payslip)
    {
        $payslip->load(['employee.position', 'employee.department', 'payrollPeriod', 'lines']);

        $pdf = Pdf::loadView('pdf.payslip', ['payslip' => $payslip])
            ->setPaper('a4');

        $name = str_replace(' ', '_', $payslip->employee?->staff_no ?? 'payslip');

        return $pdf->download("payslip_{$name}_{$payslip->id}.pdf");
    }
}
