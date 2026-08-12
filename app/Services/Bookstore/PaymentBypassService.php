<?php

namespace App\Services\Bookstore;

use App\Enums\BookRequestEvent;
use App\Enums\BookRequestStatus;
use App\Enums\PaymentBypassStatus;
use App\Enums\Permission;
use App\Models\BookPaymentBypass;
use App\Models\BookRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * "Pay later" — releasing the payment gate before the money is in.
 *
 * Two people, two written statements: Finance gives a REASON for deferring, and
 * an authoriser gives a JUSTIFICATION for accepting the debt. Neither is
 * optional, because this is the one path that lets books leave the store unpaid
 * and it has to be answerable afterwards.
 *
 * Approving does not forgive the money. The amount stays outstanding, appears on
 * the deferred-payment report, and goes overdue on the promised date until
 * somebody settles it.
 */
class PaymentBypassService
{
    public function __construct(private readonly WorkflowNotifier $notifier)
    {
    }

    /** Finance asks for the gate to be opened on credit. */
    public function request(BookRequest $request, User $actor, string $reason, ?string $promisedOn = null): BookPaymentBypass
    {
        $this->assertPermission($actor, Permission::REQUEST_PAYMENT_BYPASS);

        if ($request->status !== BookRequestStatus::AWAITING_PAYMENT) {
            throw new WorkflowException(
                'A deferral only makes sense while the request is sitting at the payment gate.'
            );
        }

        $request->load('paymentBypasses');

        if ($request->pendingBypass()) {
            throw new WorkflowException('A deferral is already awaiting a decision on this request.');
        }

        if ($request->activeBypass()) {
            throw new WorkflowException('This request already has an approved deferral.');
        }

        if (trim($reason) === '') {
            throw new WorkflowException('Say why the payment is being deferred — the reason is the record.');
        }

        return DB::transaction(function () use ($request, $actor, $reason, $promisedOn) {
            $bypass = $request->paymentBypasses()->create([
                'reference'    => BookPaymentBypass::nextReference(),
                'amount'       => $request->outstanding_amount,
                'promised_on'  => $promisedOn,
                'reason'       => trim($reason),
                'status'       => PaymentBypassStatus::PENDING,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);

            $this->notifier->fire($request, BookRequestEvent::BYPASS_REQUESTED, $actor, $reason);

            return $bypass;
        });
    }

    /**
     * An authoriser accepts the debt. This is the moment the books become
     * releasable without money, so the justification is mandatory.
     */
    public function approve(BookPaymentBypass $bypass, User $actor, string $justification): BookPaymentBypass
    {
        $this->assertPermission($actor, Permission::APPROVE_PAYMENT_BYPASS);
        $this->assertPending($bypass);

        if (trim($justification) === '') {
            throw new WorkflowException('A deferral cannot be approved without a written justification.');
        }

        // Whoever asked for the deferral cannot also grant it — the same
        // two-person rule that governs the rest of the workflow.
        if ((int) $bypass->requested_by === $actor->id) {
            throw new WorkflowException(
                'The person who requested this deferral cannot authorise it. Ask another approver.'
            );
        }

        return DB::transaction(function () use ($bypass, $actor, $justification) {
            $bypass->update([
                'status'        => PaymentBypassStatus::APPROVED,
                'decided_by'    => $actor->id,
                'decided_at'    => now(),
                'justification' => trim($justification),
            ]);

            $this->notifier->fire($bypass->bookRequest, BookRequestEvent::BYPASS_APPROVED, $actor, $justification);

            return $bypass->refresh();
        });
    }

    public function reject(BookPaymentBypass $bypass, User $actor, string $reason): BookPaymentBypass
    {
        $this->assertPermission($actor, Permission::APPROVE_PAYMENT_BYPASS);
        $this->assertPending($bypass);

        if (trim($reason) === '') {
            throw new WorkflowException('Say why the deferral is being declined.');
        }

        return DB::transaction(function () use ($bypass, $actor, $reason) {
            $bypass->update([
                'status'           => PaymentBypassStatus::REJECTED,
                'decided_by'       => $actor->id,
                'decided_at'       => now(),
                'rejection_reason' => trim($reason),
            ]);

            $this->notifier->fire($bypass->bookRequest, BookRequestEvent::BYPASS_REJECTED, $actor, $reason);

            return $bypass->refresh();
        });
    }

    /** The deferred money finally came in — closes the debt. */
    public function settle(BookPaymentBypass $bypass, User $actor): BookPaymentBypass
    {
        $this->assertPermission($actor, Permission::VERIFY_BOOK_PAYMENT);

        if (! $bypass->status->isOutstandingDebt()) {
            throw new WorkflowException('Only an approved, unsettled deferral can be settled.');
        }

        $bypass->update([
            'status'     => PaymentBypassStatus::SETTLED,
            'settled_at' => now(),
            'settled_by' => $actor->id,
        ]);

        return $bypass->refresh();
    }

    protected function assertPending(BookPaymentBypass $bypass): void
    {
        if (! $bypass->isPending()) {
            throw new WorkflowException('This deferral has already been decided.');
        }
    }

    protected function assertPermission(User $actor, Permission $permission): void
    {
        if (! $actor->can($permission->value)) {
            throw new WorkflowException('You do not have permission to do that.');
        }
    }
}
