<?php

use App\Enums\BookPaymentMethod;
use App\Enums\BookPaymentStatus;
use App\Enums\BookRequestStage;
use App\Enums\BookRequestStatus;
use App\Enums\RequestDestination;
use App\Enums\StockMovementType;
use App\Models\BookRequest;
use App\Models\BookTitle;
use App\Models\Center;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StoreRoom;
use App\Models\User;
use App\Services\Bookstore\BookRequestWorkflow;
use App\Services\Bookstore\StockLedger;
use App\Services\Bookstore\WorkflowException;
use Spatie\Permission\Models\Permission;

/** A user holding exactly the named permissions and nothing else. */
function actorWith(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission]);
        $user->givePermissionTo($permission);
    }

    return $user;
}

beforeEach(function () {
    $this->ledger   = app(StockLedger::class);
    $this->workflow = app(BookRequestWorkflow::class);

    $store         = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $store->id, 'code' => 'A']);
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02']);

    $this->title = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am',
        'unit_price' => 150, 'reorder_level' => 20,
    ]);

    $this->coordinator = actorWith(['request_books', 'receive_books']);
    $this->verifier    = actorWith(['verify_book_request', 'approve_book_request']);
    $this->finance     = actorWith(['verify_book_payment']);
    $this->approver    = actorWith(['approve_book_request']);
    $this->storeKeeper = actorWith(['dispatch_books']);

    $this->center = Center::create(['name' => 'Halaba', 'code' => 'HLB', 'student_count' => 90]);

    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 250, $this->storeKeeper);

    $this->makeRequest = function (int $quantity = 90): BookRequest {
        $request = BookRequest::create([
            'request_number'   => BookRequest::nextNumber(),
            'requester_id'     => $this->coordinator->id,
            'destination_type' => RequestDestination::CENTER,
            'center_id'        => $this->center->id,
            'student_count'    => 90,
            'status'           => BookRequestStatus::DRAFT,
        ]);

        $request->items()->create([
            'book_title_id'      => $this->title->id,
            'quantity_requested' => $quantity,
            'unit_price'         => 150,
            'line_total'         => $quantity * 150,
        ]);

        $request->refresh()->refreshTotals();

        return $request->fresh();
    };

    $this->settle = function (BookRequest $request): void {
        $payment = $request->payments()->create([
            'amount'                => $request->total_amount,
            'method'                => BookPaymentMethod::BANK_TRANSFER,
            'bank_name'             => 'CBE',
            'transaction_reference' => 'FT2603XY',
            'crv_number'            => 'CRV-00871',
            'paid_on'               => now()->toDateString(),
            'status'                => BookPaymentStatus::PENDING,
            'recorded_by'           => $this->coordinator->id,
        ]);

        $payment->verify($this->finance);
    };
});

it('will not submit a request with no lines', function () {
    $request = BookRequest::create([
        'request_number'   => BookRequest::nextNumber(),
        'requester_id'     => $this->coordinator->id,
        'destination_type' => RequestDestination::CENTER,
        'center_id'        => $this->center->id,
        'status'           => BookRequestStatus::DRAFT,
    ]);

    expect(fn () => $this->workflow->submit($request, $this->coordinator))
        ->toThrow(WorkflowException::class);
});

it('reserves stock at verification rather than deducting it', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    $this->workflow->verify($request, $this->verifier);

    expect($request->fresh()->status)->toBe(BookRequestStatus::AWAITING_PAYMENT)
        ->and($this->title->fresh()->total_on_hand)->toBe(250)
        ->and($this->title->fresh()->total_reserved)->toBe(90)
        ->and($this->title->fresh()->total_available)->toBe(160);
});

it('refuses to verify beyond what the shelves can cover', function () {
    $request = $this->workflow->submit(($this->makeRequest)(300), $this->coordinator);

    expect(fn () => $this->workflow->verify($request, $this->verifier))
        ->toThrow(RuntimeException::class);
});

it('lets a verifier approve a smaller quantity than was asked for', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $item    = $request->items()->first();

    $this->workflow->verify($request, $this->verifier, [$item->id => 60]);

    expect($item->fresh()->quantity_approved)->toBe(60)
        ->and((int) $request->fresh()->total_quantity)->toBe(60)
        ->and((float) $request->fresh()->total_amount)->toBe(9000.0)
        ->and($this->title->fresh()->total_reserved)->toBe(60);
});

it('rejects an approved quantity larger than the one requested', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $item    = $request->items()->first();

    expect(fn () => $this->workflow->verify($request, $this->verifier, [$item->id => 200]))
        ->toThrow(WorkflowException::class);
});

it('refuses every transition that skips a stage', function () {
    $request = ($this->makeRequest)();

    expect(fn () => $this->workflow->verify($request, $this->verifier))
        ->toThrow(WorkflowException::class);

    $this->workflow->submit($request, $this->coordinator);

    expect(fn () => $this->workflow->verifyPayment($request->fresh(), $this->finance))
        ->toThrow(WorkflowException::class);
    expect(fn () => $this->workflow->approve($request->fresh(), $this->approver))
        ->toThrow(WorkflowException::class);
});

it('holds the payment stage until the money is verified in full', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request, $this->verifier);

    expect(fn () => $this->workflow->verifyPayment($request->fresh(), $this->finance))
        ->toThrow(WorkflowException::class);

    ($this->settle)($request->fresh());
    $this->workflow->verifyPayment($request->fresh(), $this->finance);

    expect($request->fresh()->status)->toBe(BookRequestStatus::PAYMENT_VERIFIED);
});

it('stops the person who verified a request from also approving it', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request, $this->verifier);
    ($this->settle)($request->fresh());
    $this->workflow->verifyPayment($request->fresh(), $this->finance);

    // The verifier holds approve_book_request, so this can only fail on the rule.
    expect(fn () => $this->workflow->approve($request->fresh(), $this->verifier))
        ->toThrow(WorkflowException::class);

    $this->workflow->approve($request->fresh(), $this->approver);

    expect($request->fresh()->status)->toBe(BookRequestStatus::APPROVED);
});

it('refuses a stage to an actor without its permission', function () {
    $request  = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $outsider = actorWith(['view_bookstore']);

    expect(fn () => $this->workflow->verify($request, $outsider))
        ->toThrow(WorkflowException::class);
});

it('records one approval row per stage with the actor and the moment', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request, $this->verifier);
    ($this->settle)($request->fresh());
    $this->workflow->verifyPayment($request->fresh(), $this->finance);
    $this->workflow->approve($request->fresh(), $this->approver);

    $approvals = $request->fresh()->approvals;

    expect($approvals)->toHaveCount(4)
        ->and($approvals->pluck('stage')->all())->toBe([
            BookRequestStage::SUBMISSION,
            BookRequestStage::VERIFICATION,
            BookRequestStage::PAYMENT,
            BookRequestStage::APPROVAL,
        ])
        ->and($approvals->last()->actor_id)->toBe($this->approver->id);
});

it('gives reserved stock back when a request is rejected', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request, $this->verifier);

    expect($this->title->fresh()->total_reserved)->toBe(90);

    $this->workflow->reject($request->fresh(), $this->verifier, BookRequestStage::VERIFICATION, 'Duplicate request.');

    expect($request->fresh()->status)->toBe(BookRequestStatus::REJECTED)
        ->and($this->title->fresh()->total_reserved)->toBe(0)
        ->and($this->title->fresh()->total_on_hand)->toBe(250);
});

it('gives reserved stock back when a requester cancels', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request, $this->verifier);

    $this->workflow->cancel($request->fresh(), $this->coordinator, 'No longer needed.');

    expect($request->fresh()->status)->toBe(BookRequestStatus::CANCELLED)
        ->and($this->title->fresh()->total_reserved)->toBe(0);
});

it('offers only the actions the current stage and actor allow', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    $forVerifier = $this->workflow->availableActions($request->fresh(), $this->verifier);
    expect($forVerifier['verify'])->toBeTrue()
        ->and($forVerifier['approve'])->toBeFalse()
        ->and($forVerifier['dispatch'])->toBeFalse();

    $forCoordinator = $this->workflow->availableActions($request->fresh(), $this->coordinator);
    expect($forCoordinator['verify'])->toBeFalse()
        ->and($forCoordinator['cancel'])->toBeTrue();
});
