<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeLoanPaymentType;
use App\Enums\EmployeeLoanStatus;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\Payroll\EmployeeLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Manages employee salary loans: issuing a loan, tracking the balance that
 * payroll auto-deducts each month, and recording manual extra/settlement
 * payments. Access requires the `manage deductions` permission.
 */
class EmployeeLoanController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Finance/Loans/Index', self::pageProps($request->user()));
    }

    /** Shared payload (also served by the ModuleController for the admin nav). */
    public static function pageProps($user): array
    {
        $loans = EmployeeLoan::with(['employee:id,full_name_en,staff_no'])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->latest()
            ->get()
            ->map(fn ($l) => self::present($l));

        $active = $loans->where('status', EmployeeLoanStatus::Active->value);

        return [
            'loans' => $loans->values(),
            'employees' => Employee::where('is_active', true)
                ->orderBy('full_name_en')
                ->get(['id', 'full_name_en', 'staff_no', 'base_salary']),
            'summary' => [
                'active_count'       => $active->count(),
                'total_outstanding'  => round($active->sum('balance'), 2),
                'monthly_commitment' => round($active->sum('monthly_amount'), 2),
                'total_disbursed'    => round($loans->sum('principal_amount'), 2),
            ],
            'can' => [
                'manage' => (bool) $user?->can('manage deductions'),
            ],
        ];
    }

    /** Flatten a loan (with its derived amounts + ledger) for the front end. */
    public static function present(EmployeeLoan $loan): array
    {
        $loan->loadMissing('payments.payrollPeriod:id,name', 'payments.createdBy:id,name');

        return [
            'id'               => $loan->id,
            'reference'        => $loan->reference,
            'employee_id'      => $loan->employee_id,
            'employee'         => $loan->employee?->full_name_en,
            'staff_no'         => $loan->employee?->staff_no,
            'principal_amount' => (float) $loan->principal_amount,
            'monthly_amount'   => (float) $loan->monthly_amount,
            'duration_months'  => $loan->duration_months,
            'start_date'       => $loan->start_date?->toDateString(),
            'status'           => $loan->status->value,
            'status_label'     => $loan->status->label(),
            'notes'            => $loan->notes,
            'amount_paid'      => round($loan->amount_paid, 2),
            'balance'          => $loan->balance,
            'months_paid'      => $loan->months_paid,
            'months_remaining' => $loan->months_remaining,
            'progress_percent' => $loan->progress_percent,
            'payments'         => $loan->payments->map(fn ($p) => [
                'id'      => $p->id,
                'amount'  => (float) $p->amount,
                'type'    => $p->type->value,
                'type_label' => $p->type->label(),
                'note'    => $p->note,
                'paid_on' => $p->paid_on?->toDateString(),
                'period'  => $p->payrollPeriod?->name,
                'by'      => $p->createdBy?->name,
            ])->values(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'      => ['required', 'exists:employees,id'],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'monthly_amount'   => ['required', 'numeric', 'min:1', 'lte:principal_amount'],
            'start_date'       => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        // Fixed monthly amount ⇒ nominal term is however many months it takes.
        $duration = (int) ceil($data['principal_amount'] / $data['monthly_amount']);

        DB::transaction(function () use ($data, $duration, $request) {
            EmployeeLoan::create([
                'employee_id'      => $data['employee_id'],
                'reference'        => self::nextReference(),
                'principal_amount' => $data['principal_amount'],
                'monthly_amount'   => $data['monthly_amount'],
                'duration_months'  => $duration,
                'start_date'       => $data['start_date'],
                'status'           => EmployeeLoanStatus::Active,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Loan issued. It will be deducted automatically on each payroll run.');
    }

    /** Record a manual extra payment or settle the remaining balance at once. */
    public function payment(Request $request, EmployeeLoan $loan, EmployeeLoanService $service)
    {
        if ($loan->status === EmployeeLoanStatus::Cancelled) {
            return back()->with('error', 'This loan has been cancelled.');
        }
        if ($loan->balance <= 0) {
            return back()->with('error', 'This loan is already fully paid.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$loan->balance],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $service->recordManualPayment($loan, (float) $data['amount'], $request->user(), $data['note'] ?? null);

        if (! $payment) {
            return back()->with('error', 'Nothing left to pay on this loan.');
        }

        $loan->refresh();
        $message = $loan->balance <= 0
            ? 'Payment recorded — the loan is now fully paid and cleared.'
            : 'Payment of '.number_format((float) $payment->amount, 2).' recorded. Remaining balance: '.number_format($loan->balance, 2).'.';

        return back()->with('success', $message);
    }

    /** Cancel (write off) a loan so payroll stops deducting it. */
    public function cancel(Request $request, EmployeeLoan $loan)
    {
        if ($loan->status === EmployeeLoanStatus::Paid) {
            return back()->with('error', 'A fully paid loan cannot be cancelled.');
        }

        $loan->update(['status' => EmployeeLoanStatus::Cancelled]);

        return back()->with('success', 'Loan cancelled. It will no longer be deducted from payroll.');
    }

    /** Next human reference like LN-2026-0007, unique across the table. */
    private static function nextReference(): string
    {
        $year = now()->format('Y');
        $seq = EmployeeLoan::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = 'LN-'.$year.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        } while (EmployeeLoan::where('reference', $reference)->exists());

        return $reference;
    }
}
