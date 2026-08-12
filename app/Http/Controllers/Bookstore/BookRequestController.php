<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\BookRequestStage;
use App\Enums\BookRequestStatus;
use App\Enums\RequestDestination;
use App\Http\Controllers\Controller;
use App\Models\BookRequest;
use App\Models\BookTitle;
use App\Models\Campus;
use App\Models\Center;
use App\Services\Bookstore\BookRequestWorkflow;
use App\Services\Bookstore\StockLedger;
use App\Services\Bookstore\WorkflowException;
use App\Services\Bookstore\WorkflowNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The request journey, end to end. Every state change delegates to
 * {@see BookRequestWorkflow} — this controller only validates input and turns a
 * refused transition into a message.
 */
class BookRequestController extends Controller
{
    public function __construct(
        private readonly BookRequestWorkflow $workflow,
        private readonly StockLedger $ledger,
        private readonly WorkflowNotifier $notifier,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'search', 'mine']);

        $requests = BookRequest::query()
            ->with(['requester:id,name', 'center:id,name', 'campus:id,name,name_en'])
            ->withCount('items')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['mine'] ?? null, fn ($q) => $q->forUser($request->user()))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($sub) => $sub
                ->where('request_number', 'like', "%{$term}%")
                ->orWhere('contact_name', 'like', "%{$term}%")))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bookstore/Requests/Index', [
            'requests' => $requests,
            'filters'  => $filters,
            'statuses' => BookRequestStatus::options(),
            'queue'    => $this->queueCounts(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Bookstore/Requests/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedHeader($request);
        $lines     = $this->validatedLines($request);

        $bookRequest = DB::transaction(function () use ($validated, $lines, $request) {
            $bookRequest = BookRequest::create([
                ...$validated,
                'request_number' => BookRequest::nextNumber(),
                'requester_id'   => $request->user()->id,
                'status'         => BookRequestStatus::DRAFT,
            ]);

            $this->syncItems($bookRequest, $lines);

            return $bookRequest;
        });

        return redirect()->route('bookstore.requests.show', $bookRequest)
            ->with('success', "Request {$bookRequest->request_number} saved as a draft. Submit it when the lines are right.");
    }

    public function show(BookRequest $bookRequest, Request $request): Response
    {
        $bookRequest->load([
            'requester:id,name',
            'center',
            'campus:id,name,name_en',
            'items.bookTitle:id,code,title,unit_price',
            'approvals.actor:id,name',
            'payments.recordedBy:id,name',
            'payments.verifiedBy:id,name',
            'dispatches.items.bookTitle:id,code,title',
            'dispatches.items.shelfSection',
            'paymentBypasses.requestedBy:id,name',
            'paymentBypasses.decidedBy:id,name',
        ]);

        // Show the verifier what the shelves can actually cover, per line.
        $availability = $bookRequest->items->mapWithKeys(fn ($item) => [
            $item->id => $this->ledger->availableFor($item->bookTitle),
        ]);

        return Inertia::render('Bookstore/Requests/Show', [
            'request'      => $bookRequest,
            'availability' => $availability,
            'actions'      => $this->workflow->availableActions($bookRequest, $request->user()),
            'stages'       => collect(BookRequestStage::cases())->map(fn (BookRequestStage $stage) => [
                'value'      => $stage->value,
                'label'      => $stage->label(),
                'caption_am' => $stage->captionAm(),
            ]),
            'financials' => [
                'total'       => (float) $bookRequest->total_amount,
                'paid'        => $bookRequest->paid_amount,
                'outstanding' => $bookRequest->outstanding_amount,
                'gate_open'   => $bookRequest->paymentGateIsOpen(),
            ],
            // Who owes the next action, and how long they have owed it.
            'waiting' => [
                'description' => $bookRequest->status->awaitingDescription(),
                'owners'      => $this->notifier->currentOwners($bookRequest)->pluck('name')->take(5)->values(),
                'since'       => $bookRequest->currentStageEnteredAt()?->toIso8601String(),
                'age_hours'   => $bookRequest->current_stage_age === null
                    ? null
                    : round($bookRequest->current_stage_age / 3600, 1),
            ],
            'bypasses' => $bookRequest->paymentBypasses,
        ]);
    }

    public function edit(BookRequest $bookRequest): Response
    {
        abort_unless($bookRequest->status->isEditable(), 403, 'Only a draft request can be edited.');

        $bookRequest->load('items.bookTitle:id,code,title,unit_price');

        return Inertia::render('Bookstore/Requests/Edit', [
            'request' => $bookRequest,
            'options' => $this->formOptions(),
        ]);
    }

    public function update(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        abort_unless($bookRequest->status->isEditable(), 403, 'Only a draft request can be edited.');

        $validated = $this->validatedHeader($request);
        $lines     = $this->validatedLines($request);

        DB::transaction(function () use ($bookRequest, $validated, $lines) {
            $bookRequest->update($validated);
            $this->syncItems($bookRequest, $lines);
        });

        return redirect()->route('bookstore.requests.show', $bookRequest)
            ->with('success', 'Request updated.');
    }

    // ── Workflow transitions ───────────────────────────────────────────────

    public function submit(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        return $this->run(fn () => $this->workflow->submit($bookRequest, $request->user()),
            'Request submitted for verification.');
    }

    public function verify(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate([
            'quantities'   => 'array',
            'quantities.*' => 'integer|min:0',
            'note'         => 'nullable|string|max:1000',
        ]);

        return $this->run(fn () => $this->workflow->verify(
            $bookRequest,
            $request->user(),
            $validated['quantities'] ?? [],
            $validated['note'] ?? null
        ), 'Availability verified and stock reserved. Finance can now confirm the payment.');
    }

    public function verifyPayment(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);

        return $this->run(fn () => $this->workflow->verifyPayment($bookRequest, $request->user(), $validated['note'] ?? null),
            'Payment verified. The request is ready for final approval.');
    }

    public function approve(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);

        return $this->run(fn () => $this->workflow->approve($bookRequest, $request->user(), $validated['note'] ?? null),
            'Request approved. The store can dispatch it.');
    }

    public function confirmReceipt(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);

        return $this->run(fn () => $this->workflow->confirmReceipt($bookRequest, $request->user(), $validated['note'] ?? null),
            'Receipt confirmed. Thank you.');
    }

    public function reject(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate([
            'stage'  => 'required|string|in:'.implode(',', array_column(BookRequestStage::cases(), 'value')),
            'reason' => 'required|string|max:1000',
        ]);

        return $this->run(fn () => $this->workflow->reject(
            $bookRequest,
            $request->user(),
            BookRequestStage::from($validated['stage']),
            $validated['reason']
        ), 'Request rejected and any reserved stock released.');
    }

    public function cancel(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        abort_unless((int) $bookRequest->requester_id === $request->user()->id, 403);

        $validated = $request->validate(['reason' => 'nullable|string|max:1000']);

        return $this->run(fn () => $this->workflow->cancel($bookRequest, $request->user(), $validated['reason'] ?? null),
            'Request cancelled.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Run a workflow call and turn a refusal into a message rather than a 500. */
    protected function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (WorkflowException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }

    /** @return array<string, mixed> */
    protected function validatedHeader(Request $request): array
    {
        $validated = $request->validate([
            'destination_type' => 'required|string|in:'.implode(',', array_column(RequestDestination::cases(), 'value')),
            'center_id'        => 'nullable|required_if:destination_type,center|exists:centers,id',
            'campus_id'        => 'nullable|required_if:destination_type,campus|exists:campuses,id',
            'student_count'    => 'required|integer|min:0',
            'contact_name'     => 'nullable|string|max:255',
            'contact_phone'    => 'nullable|string|max:30',
            'needed_by'        => 'nullable|date',
            'notes'            => 'nullable|string',
        ]);

        // Only one destination may be set, whichever type was chosen.
        if ($validated['destination_type'] === RequestDestination::CENTER->value) {
            $validated['campus_id'] = null;
        } else {
            $validated['center_id'] = null;
        }

        return $validated;
    }

    /** @return array<int, array{book_title_id: int, quantity_requested: int, remark: string|null}> */
    protected function validatedLines(Request $request): array
    {
        $validated = $request->validate([
            'items'                      => 'required|array|min:1',
            'items.*.book_title_id'      => 'required|exists:book_titles,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
            'items.*.remark'             => 'nullable|string|max:255',
        ]);

        return $validated['items'];
    }

    /** Replace the lines wholesale — simpler and safer than diffing a draft. */
    protected function syncItems(BookRequest $bookRequest, array $lines): void
    {
        $bookRequest->items()->delete();

        foreach ($lines as $line) {
            $title = BookTitle::findOrFail($line['book_title_id']);

            $bookRequest->items()->create([
                'book_title_id'      => $title->id,
                'quantity_requested' => $line['quantity_requested'],
                'unit_price'         => $title->unit_price,
                'line_total'         => round($line['quantity_requested'] * (float) $title->unit_price, 2),
                'remark'             => $line['remark'] ?? null,
            ]);
        }

        $bookRequest->refresh()->refreshTotals();
    }

    /** How much work is sitting at each stage — the pipeline banner. */
    protected function queueCounts(): array
    {
        return [
            'submitted'        => BookRequest::awaiting(BookRequestStatus::SUBMITTED)->count(),
            'awaiting_payment' => BookRequest::awaiting(BookRequestStatus::AWAITING_PAYMENT)->count(),
            'payment_verified' => BookRequest::awaiting(BookRequestStatus::PAYMENT_VERIFIED)->count(),
            'approved'         => BookRequest::awaiting(BookRequestStatus::APPROVED)->count(),
        ];
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'centers'  => Center::active()->orderBy('name')->get(['id', 'name', 'code', 'student_count', 'coordinator_name', 'coordinator_phone']),
            'campuses' => Campus::orderBy('name')->get(['id', 'name', 'name_en']),
            'titles'   => BookTitle::active()->with('stocks')->orderBy('title')->get()
                ->map(fn (BookTitle $t) => [
                    'id'         => $t->id,
                    'code'       => $t->code,
                    'title'      => $t->title,
                    'unit_price' => (float) $t->unit_price,
                    'available'  => $t->total_available,
                ]),
        ];
    }
}
