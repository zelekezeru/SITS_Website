<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Enums\MedicalAllowanceClaimStatus;
use App\Models\Document;
use App\Models\Employee;
use App\Models\MedicalAllowanceClaim;
use App\Models\PayrollPeriod;
use App\Models\Setting;
use App\Services\MedicalAllowanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Medical allowance reimbursement claims: submitted by Finance with one or
 * more bill documents, reviewed and approved by an admin (locking in the
 * covered/employee split for the year), then marked paid once Finance
 * disburses the reimbursement. Bill documents are stored on the private
 * 'local' disk — never web-served — same as attendance-permission evidence.
 */
class MedicalAllowanceClaimController extends Controller
{
    private const MAX_FILES = 10;

    public function index(Request $request)
    {
        return Inertia::render('Admin/Finance/MedicalAllowance/Index', self::pageProps($request->user()));
    }

    /** Shared payload for the admin (ModuleController) and Finance views. */
    public static function pageProps($user): array
    {
        $claims = MedicalAllowanceClaim::with([
            'employee:id,full_name_en,staff_no',
            'createdBy:id,name',
            'reviewedBy:id,name',
            'paidBy:id,name',
            'payrollPeriod:id,name',
            'documents',
        ])->latest()->get()->map(fn ($c) => self::present($c));

        $currentYear = (int) now()->format('Y');

        $employees = Employee::where('is_active', true)
            ->where('medical_allowance_enabled', true)
            ->where('employment_type', EmploymentType::FullTime->value)
            ->orderBy('full_name_en')
            ->get(['id', 'full_name_en', 'staff_no'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'full_name_en' => $e->full_name_en,
                'staff_no' => $e->staff_no,
                'reserved_this_year' => round(
                    MedicalAllowanceClaim::reservedTotalForYear($e->id, $currentYear),
                    2
                ),
            ]);

        $approvedAwaitingPayment = $claims->where('status', MedicalAllowanceClaimStatus::Approved->value);

        return [
            'claims' => $claims->values(),
            'employees' => $employees->values(),
            'periods' => PayrollPeriod::monthly()->forActiveYear()
                ->orderByDesc('start_date')->get(['id', 'name']),
            'settings' => [
                'policy_year' => $currentYear,
                'full_coverage_limit' => (float) Setting::get('medical_full_coverage_limit', 5000),
                'max_coverage_limit' => (float) Setting::get('medical_max_coverage_limit', 10000),
                'coinsurance_rate' => (float) Setting::get('medical_coinsurance_rate', 50),
            ],
            'summary' => [
                'pending_count' => $claims->where('status', MedicalAllowanceClaimStatus::PendingReview->value)->count(),
                'approved_awaiting_payment' => $approvedAwaitingPayment->count(),
                'awaiting_payment_total' => round($approvedAwaitingPayment->sum('covered_amount'), 2),
                'paid_this_year' => round(
                    $claims->where('status', MedicalAllowanceClaimStatus::Paid->value)
                        ->filter(fn ($c) => $c['policy_year'] === $currentYear)
                        ->sum('covered_amount'),
                    2
                ),
            ],
            'can' => [
                'request' => (bool) $user?->can('request medical allowance'),
                'approve' => (bool) $user?->can('approve medical allowance'),
                'configure' => (bool) $user?->can('configure payroll'),
            ],
        ];
    }

    public static function present(MedicalAllowanceClaim $claim): array
    {
        return [
            'id' => $claim->id,
            'reference' => $claim->reference,
            'employee_id' => $claim->employee_id,
            'employee' => $claim->employee?->full_name_en,
            'staff_no' => $claim->employee?->staff_no,
            'policy_year' => $claim->policy_year,
            'bill_amount' => (float) $claim->bill_amount,
            'covered_amount' => $claim->covered_amount !== null ? (float) $claim->covered_amount : null,
            'employee_amount' => $claim->employee_amount !== null ? (float) $claim->employee_amount : null,
            'incident_date' => $claim->incident_date?->toDateString(),
            'status' => $claim->status->value,
            'status_label' => $claim->status->label(),
            'notes' => $claim->notes,
            'rejection_reason' => $claim->rejection_reason,
            'created_by' => $claim->createdBy?->name,
            'created_at' => $claim->created_at?->toDateTimeString(),
            'reviewed_by' => $claim->reviewedBy?->name,
            'reviewed_at' => $claim->reviewed_at?->toDateTimeString(),
            'paid_by' => $claim->paidBy?->name,
            'paid_at' => $claim->paid_at?->toDateTimeString(),
            'paid_on' => $claim->paid_on?->toDateString(),
            'payment_reference' => $claim->payment_reference,
            'payroll_period_id' => $claim->payroll_period_id,
            'payroll_period' => $claim->payrollPeriod?->name,
            'is_pending' => $claim->isPending(),
            'accepts_documents' => $claim->acceptsDocuments(),
            'documents' => $claim->documents->map(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'mime' => $d->mime,
                'size' => $d->size,
                'uploaded_by' => $d->uploadedBy?->name,
                'uploaded_at' => $d->created_at?->toDateTimeString(),
                'url' => route('admin.medical-allowance.documents.download', [$claim, $d]),
            ])->values(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')
                    ->where('is_active', true)
                    ->where('medical_allowance_enabled', true)
                    ->where('employment_type', EmploymentType::FullTime->value),
            ],
            'bill_amount' => ['required', 'numeric', 'min:1'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'employee_id.required' => 'Select an eligible, full-time employee enrolled in the medical allowance.',
            'files.required' => 'Upload at least one medical bill.',
        ]);

        $claim = DB::transaction(function () use ($data, $request) {
            $claim = MedicalAllowanceClaim::create([
                'employee_id' => $data['employee_id'],
                'reference' => self::nextReference(),
                'policy_year' => (int) now()->format('Y'),
                'bill_amount' => $data['bill_amount'],
                'incident_date' => $data['incident_date'] ?? null,
                'status' => MedicalAllowanceClaimStatus::PendingReview,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            self::storeFiles($claim, $request->file('files', []), $request->user()->id);

            return $claim;
        });

        return back()->with('success', "Claim {$claim->reference} submitted for approval.");
    }

    /** Attach further evidence to a claim that hasn't been disbursed yet. */
    public function storeDocuments(Request $request, MedicalAllowanceClaim $claim)
    {
        if (! $claim->acceptsDocuments()) {
            return back()->with('error', 'Documents can no longer be attached to this claim.');
        }

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        self::storeFiles($claim, $data['files'], $request->user()->id);

        return back()->with('success', 'Document(s) attached.');
    }

    public function destroyDocument(MedicalAllowanceClaim $claim, Document $document)
    {
        abort_if($document->documentable_type !== MedicalAllowanceClaim::class || $document->documentable_id !== $claim->id, 404);

        if (! $claim->isPending()) {
            return back()->with('error', 'Documents can only be removed while the claim is pending review.');
        }

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    /** Stream a claim's bill document to authorised users only. */
    public function downloadDocument(Request $request, MedicalAllowanceClaim $claim, Document $document)
    {
        abort_if($document->documentable_type !== MedicalAllowanceClaim::class || $document->documentable_id !== $claim->id, 404);

        $user = $request->user();
        abort_unless(
            $user->can('request medical allowance') || $user->can('approve medical allowance'),
            403
        );

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->title);
    }

    public function approve(Request $request, MedicalAllowanceClaim $claim)
    {
        if (! $claim->isPending()) {
            return back()->with('error', 'Only a pending claim can be approved.');
        }

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $claim->approve($request->user(), $data['notes'] ?? null);

        return back()->with('success', "Claim {$claim->reference} approved — {$claim->fresh()->covered_amount} ETB covered by the institution.");
    }

    public function reject(Request $request, MedicalAllowanceClaim $claim)
    {
        if (! $claim->isPending()) {
            return back()->with('error', 'Only a pending claim can be rejected.');
        }

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $claim->reject($request->user(), $data['rejection_reason']);

        return back()->with('success', "Claim {$claim->reference} rejected.");
    }

    public function cancel(MedicalAllowanceClaim $claim)
    {
        if (! $claim->isPending()) {
            return back()->with('error', 'Only a pending claim can be cancelled.');
        }

        $claim->cancel();

        return back()->with('success', "Claim {$claim->reference} cancelled.");
    }

    /** Record the reimbursement as disbursed and attribute it to a payroll period for the payslip. */
    public function recordPayment(Request $request, MedicalAllowanceClaim $claim)
    {
        if ($claim->status !== MedicalAllowanceClaimStatus::Approved) {
            return back()->with('error', 'Only an approved claim awaiting payment can be recorded as paid.');
        }

        $data = $request->validate([
            'paid_on' => ['required', 'date'],
            'payroll_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $claim->recordPayment(
            $request->user(),
            $data['paid_on'],
            $data['payroll_period_id'] ?? null,
            $data['payment_reference'] ?? null,
        );

        return back()->with('success', "Payment recorded for claim {$claim->reference}.");
    }

    /** Update the institution-wide coverage tiers (Super Admin only). */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'full_coverage_limit' => ['required', 'numeric', 'min:0'],
            'max_coverage_limit' => ['required', 'numeric', 'gte:full_coverage_limit'],
            'coinsurance_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::set('medical_full_coverage_limit', $data['full_coverage_limit'], 'payroll', 'decimal');
        Setting::set('medical_max_coverage_limit', $data['max_coverage_limit'], 'payroll', 'decimal');
        Setting::set('medical_coinsurance_rate', $data['coinsurance_rate'], 'payroll', 'decimal');

        return back()->with('success', 'Medical allowance policy updated.');
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    private static function storeFiles(MedicalAllowanceClaim $claim, array $files, int $uploadedBy): void
    {
        foreach ($files as $file) {
            $path = $file->store('medical-allowance/'.$claim->id, 'local');

            Document::create([
                'documentable_type' => MedicalAllowanceClaim::class,
                'documentable_id' => $claim->id,
                'category' => 'medical_bill',
                'title' => $file->getClientOriginalName(),
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $uploadedBy,
            ]);
        }
    }

    /** Next human reference like MED-2026-0007, unique across the table. */
    private static function nextReference(): string
    {
        $year = now()->format('Y');
        $seq = MedicalAllowanceClaim::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = 'MED-'.$year.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        } while (MedicalAllowanceClaim::where('reference', $reference)->exists());

        return $reference;
    }
}
