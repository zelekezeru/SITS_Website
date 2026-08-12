<?php

namespace App\Services\Bookstore;

use App\Enums\ApprovalDecision;
use App\Enums\BookPaymentStatus;
use App\Enums\BookRequestEvent;
use App\Enums\BookRequestStage;
use App\Enums\BookRequestStatus;
use App\Enums\Permission;
use App\Models\BookRequest;
use App\Models\BookRequestApproval;
use App\Models\BookRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The one place a book request changes state.
 *
 *   draft → submitted → awaiting_payment → payment_verified → approved
 *         → (partially_)dispatched → received
 *
 * Every method here does the same five things: check the transition is legal,
 * check the actor may make it, mutate inside a transaction, append to the
 * approval trail, and move any stock reservation that the step implies. Keeping
 * that shape identical is what makes the journey predictable.
 */
class BookRequestWorkflow
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly WorkflowNotifier $notifier,
    ) {
    }

    // ── 1. Submission ──────────────────────────────────────────────────────

    public function submit(BookRequest $request, User $actor): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::SUBMITTED);
        $this->assertPermission($actor, BookRequestStage::SUBMISSION);

        if ($request->items()->count() === 0) {
            throw new WorkflowException('Add at least one book before submitting the request.');
        }

        return DB::transaction(function () use ($request, $actor) {
            $request->refreshTotals();
            $request->update([
                'status'       => BookRequestStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->recordApproval($request, BookRequestStage::SUBMISSION, $actor, ApprovalDecision::APPROVED);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::SUBMITTED, $actor);

            return $request;
        });
    }

    // ── 2. Availability & genuineness verification (stock is reserved here) ──

    /**
     * @param  array<int, int>  $approvedQuantities  book_request_item_id => quantity approved
     */
    public function verify(BookRequest $request, User $actor, array $approvedQuantities = [], ?string $note = null): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::AWAITING_PAYMENT);
        $this->assertPermission($actor, BookRequestStage::VERIFICATION);

        return DB::transaction(function () use ($request, $actor, $approvedQuantities, $note) {
            foreach ($request->items()->with('bookTitle')->get() as $item) {
                $approved = (int) ($approvedQuantities[$item->id] ?? $item->quantity_requested);

                if ($approved < 0 || $approved > $item->quantity_requested) {
                    throw new WorkflowException(
                        "Approved quantity for \"{$item->bookTitle->title}\" must be between 0 and {$item->quantity_requested}."
                    );
                }

                // Throws when the shelves cannot cover it — the verifier sees the
                // real number and can lower the line instead of promising air.
                $this->ledger->reserve($item->bookTitle, $approved);

                $item->quantity_approved = $approved;
                $item->refreshTotals();
                $item->save();
            }

            $request->refreshTotals();
            $request->update([
                'status'      => BookRequestStatus::AWAITING_PAYMENT,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            $this->recordApproval($request, BookRequestStage::VERIFICATION, $actor, ApprovalDecision::APPROVED, $note);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::VERIFIED, $actor, $note);

            return $request;
        });
    }

    // ── 3. Payment verification (finance) ──────────────────────────────────

    /**
     * The payment gate. It opens on one of two facts: the money is verifiably
     * in, or somebody with the authority has signed a pay-later deferral and
     * accepted the debt. Nothing else opens it.
     */
    public function verifyPayment(BookRequest $request, User $actor, ?string $note = null): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::PAYMENT_VERIFIED);
        $this->assertPermission($actor, BookRequestStage::PAYMENT);

        $request->load('paymentBypasses');

        $verified = (float) $request->payments()
            ->where('status', BookPaymentStatus::VERIFIED->value)
            ->sum('amount');

        $bypass = $request->activeBypass();

        if ($bypass === null && $verified + 0.009 < (float) $request->total_amount) {
            throw new WorkflowException(sprintf(
                'Verified payments total %s of %s. Verify the outstanding payment records, or raise a pay-later deferral for approval.',
                number_format($verified, 2),
                number_format((float) $request->total_amount, 2)
            ));
        }

        return DB::transaction(function () use ($request, $actor, $note, $bypass) {
            $request->update([
                'status'              => BookRequestStatus::PAYMENT_VERIFIED,
                'payment_verified_by' => $actor->id,
                'payment_verified_at' => now(),
            ]);

            // The trail must say *why* the gate opened — paid, or deferred under
            // whose authority — because a year later that is the only question.
            $trailNote = $bypass
                ? trim(($note ? $note.' — ' : '')."Released on pay-later deferral {$bypass->reference}, authorised by ".($bypass->decidedBy?->name ?? 'an authoriser'))
                : $note;

            $this->recordApproval($request, BookRequestStage::PAYMENT, $actor, ApprovalDecision::APPROVED, $trailNote);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::PAYMENT_VERIFIED, $actor, $trailNote);

            return $request;
        });
    }

    // ── 4. Final approval (admin) ──────────────────────────────────────────

    public function approve(BookRequest $request, User $actor, ?string $note = null): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::APPROVED);
        $this->assertPermission($actor, BookRequestStage::APPROVAL);
        $this->assertSegregation($request, $actor, BookRequestStage::APPROVAL);

        return DB::transaction(function () use ($request, $actor, $note) {
            $request->update([
                'status'      => BookRequestStatus::APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->recordApproval($request, BookRequestStage::APPROVAL, $actor, ApprovalDecision::APPROVED, $note);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::APPROVED, $actor, $note);

            return $request;
        });
    }

    // ── 5. Dispatch bookkeeping (stock movement lives in DispatchService) ───

    /**
     * Called by {@see DispatchService} once a consignment has actually left, to
     * move the request to partially/fully dispatched.
     */
    public function markDispatched(BookRequest $request, User $actor): BookRequest
    {
        $request->load('items');

        $target = $request->isFullyDispatched()
            ? BookRequestStatus::DISPATCHED
            : BookRequestStatus::PARTIALLY_DISPATCHED;

        $this->assertTransition($request, $target);

        return DB::transaction(function () use ($request, $actor, $target) {
            $request->update([
                'status'        => $target,
                'dispatched_by' => $actor->id,
                'dispatched_at' => now(),
            ]);

            $this->recordApproval($request, BookRequestStage::DISPATCH, $actor, ApprovalDecision::APPROVED);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::DISPATCHED, $actor);

            return $request;
        });
    }

    // ── 6. Receipt confirmation ────────────────────────────────────────────

    public function confirmReceipt(BookRequest $request, User $actor, ?string $note = null): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::RECEIVED);

        return DB::transaction(function () use ($request, $actor, $note) {
            $request->update([
                'status'      => BookRequestStatus::RECEIVED,
                'received_at' => now(),
            ]);

            $this->recordApproval($request, BookRequestStage::RECEIPT, $actor, ApprovalDecision::APPROVED, $note);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::RECEIVED, $actor, $note);

            return $request;
        });
    }

    // ── Rejection & cancellation ───────────────────────────────────────────

    public function reject(BookRequest $request, User $actor, BookRequestStage $stage, string $reason): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::REJECTED);
        $this->assertPermission($actor, $stage);

        return DB::transaction(function () use ($request, $actor, $stage, $reason) {
            $this->releaseReservations($request);

            $request->update([
                'status'           => BookRequestStatus::REJECTED,
                'rejection_reason' => $reason,
            ]);

            $this->recordApproval($request, $stage, $actor, ApprovalDecision::REJECTED, $reason);

            $request->refresh();
            $this->notifier->fire($request, BookRequestEvent::REJECTED, $actor, $reason);

            return $request;
        });
    }

    public function cancel(BookRequest $request, User $actor, ?string $reason = null): BookRequest
    {
        $this->assertTransition($request, BookRequestStatus::CANCELLED);

        return DB::transaction(function () use ($request, $actor, $reason) {
            $this->releaseReservations($request);

            $request->update([
                'status'           => BookRequestStatus::CANCELLED,
                'rejection_reason' => $reason,
            ]);

            $this->recordApproval(
                $request,
                BookRequestStage::SUBMISSION,
                $actor,
                ApprovalDecision::RETURNED,
                $reason ?? 'Cancelled by requester.'
            );

            return $request->refresh();
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Hand back everything this request was holding, line by line. */
    protected function releaseReservations(BookRequest $request): void
    {
        if (! $request->status->holdsReservation()) {
            return;
        }

        foreach ($request->items()->with('bookTitle')->get() as $item) {
            $outstanding = max(0, $item->quantity_approved - $item->quantity_dispatched);

            if ($outstanding > 0) {
                $this->ledger->release($item->bookTitle, $outstanding);
            }
        }
    }

    protected function assertTransition(BookRequest $request, BookRequestStatus $target): void
    {
        if (! $request->status->canTransitionTo($target)) {
            throw new WorkflowException(sprintf(
                'A request that is %s cannot become %s.',
                $request->status->label(),
                $target->label()
            ));
        }
    }

    protected function assertPermission(User $actor, BookRequestStage $stage): void
    {
        if (! $actor->can($stage->permission()->value)) {
            throw new WorkflowException(sprintf(
                'You do not have permission to act on the "%s" stage.',
                $stage->label()
            ));
        }
    }

    /**
     * Segregation of duties: whoever verified availability or the payment may
     * not also give final approval. One account must never be able to walk a
     * request from submission to dispatch on its own.
     */
    protected function assertSegregation(BookRequest $request, User $actor, BookRequestStage $stage): void
    {
        if ($stage !== BookRequestStage::APPROVAL) {
            return;
        }

        if ((int) $request->verified_by === $actor->id || (int) $request->payment_verified_by === $actor->id) {
            throw new WorkflowException(
                'The person who verified this request cannot also give final approval. Ask another approver.'
            );
        }
    }

    /**
     * Append to the trail, and freeze how long this stage waited.
     *
     * The dwell time is measured from the previous action (or from creation for
     * the first step), so "where is the lag" is a plain average over this column
     * rather than a window function across the whole history.
     */
    protected function recordApproval(
        BookRequest $request,
        BookRequestStage $stage,
        User $actor,
        ApprovalDecision $decision,
        ?string $note = null
    ): BookRequestApproval {
        $now = now();

        $previousActedAt = $request->approvals()
            ->orderByDesc('acted_at')
            ->orderByDesc('id')
            ->value('acted_at');

        $enteredStageAt = $previousActedAt
            ? \Illuminate\Support\Carbon::parse($previousActedAt)
            : $request->created_at;

        return $request->approvals()->create([
            'stage'          => $stage,
            'actor_id'       => $actor->id,
            'decision'       => $decision,
            'note'           => $note,
            'acted_at'       => $now,
            'waited_seconds' => max(0, (int) $enteredStageAt->diffInSeconds($now)),
        ]);
    }

    /**
     * What this user may do to this request right now — drives which buttons the
     * UI renders, from exactly the rules the service enforces.
     *
     * @return array<string, bool>
     */
    public function availableActions(BookRequest $request, User $user): array
    {
        $status = $request->status;

        $canApprove = $status === BookRequestStatus::PAYMENT_VERIFIED
            && $user->can(BookRequestStage::APPROVAL->permission()->value)
            && (int) $request->verified_by !== $user->id
            && (int) $request->payment_verified_by !== $user->id;

        $request->loadMissing('paymentBypasses');
        $pendingBypass = $request->pendingBypass();

        return [
            // Pay-later: Finance may ask while the request sits at the payment
            // gate; a different person with the grant decides.
            'request_bypass' => $status === BookRequestStatus::AWAITING_PAYMENT
                                && $pendingBypass === null
                                && $request->activeBypass() === null
                                && $user->can(Permission::REQUEST_PAYMENT_BYPASS->value),
            'decide_bypass'  => $pendingBypass !== null
                                && $user->can(Permission::APPROVE_PAYMENT_BYPASS->value)
                                && (int) $pendingBypass->requested_by !== $user->id,

            'edit'           => $status->isEditable() && (int) $request->requester_id === $user->id,
            'submit'         => $status === BookRequestStatus::DRAFT
                                && (int) $request->requester_id === $user->id
                                && $user->can(BookRequestStage::SUBMISSION->permission()->value),
            'verify'         => $status === BookRequestStatus::SUBMITTED
                                && $user->can(BookRequestStage::VERIFICATION->permission()->value),
            'verify_payment' => $status === BookRequestStatus::AWAITING_PAYMENT
                                && $user->can(BookRequestStage::PAYMENT->permission()->value),
            'approve'        => $canApprove,
            'dispatch'       => in_array($status, [BookRequestStatus::APPROVED, BookRequestStatus::PARTIALLY_DISPATCHED], true)
                                && $user->can(BookRequestStage::DISPATCH->permission()->value),
            'confirm_receipt' => $status === BookRequestStatus::DISPATCHED
                                && ((int) $request->requester_id === $user->id
                                    || $user->can(BookRequestStage::RECEIPT->permission()->value)),
            'reject'         => in_array($status, [
                                    BookRequestStatus::SUBMITTED,
                                    BookRequestStatus::AWAITING_PAYMENT,
                                    BookRequestStatus::PAYMENT_VERIFIED,
                                ], true)
                                && ($user->can(BookRequestStage::VERIFICATION->permission()->value)
                                    || $user->can(BookRequestStage::PAYMENT->permission()->value)
                                    || $user->can(BookRequestStage::APPROVAL->permission()->value)),
            'cancel'         => $status->canBeCancelled() && (int) $request->requester_id === $user->id,
        ];
    }

    /** Convenience for controllers building a line from a title. */
    public function priceLine(BookRequestItem $item): BookRequestItem
    {
        $item->unit_price = $item->unit_price ?: $item->bookTitle->unit_price;
        $item->refreshTotals();

        return $item;
    }
}
