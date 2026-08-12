<?php

use App\Enums\BookRequestStatus;
use App\Enums\PaymentBypassStatus;
use App\Enums\RequestDestination;
use App\Enums\StockMovementType;
use App\Models\BookRequest;
use App\Models\BookTitle;
use App\Models\Center;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StoreRoom;
use App\Models\User;
use App\Notifications\BookRequestStageChanged;
use App\Services\Bookstore\BookRequestWorkflow;
use App\Services\Bookstore\PaymentBypassService;
use App\Services\Bookstore\StockLedger;
use App\Services\Bookstore\WorkflowException;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

function bypassActor(array $permissions): User
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
    $this->bypasses = app(PaymentBypassService::class);

    $store         = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $store->id, 'code' => 'A']);
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02']);

    $this->title = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am', 'unit_price' => 150,
    ]);

    $this->coordinator = bypassActor(['request_books']);
    $this->storeKeeper = bypassActor(['verify_book_request', 'dispatch_books']);
    $this->finance     = bypassActor(['verify_book_payment', 'request_payment_bypass']);
    $this->admin       = bypassActor(['approve_book_request', 'approve_payment_bypass']);

    $this->center = Center::create(['name' => 'Halaba', 'code' => 'HLB', 'student_count' => 90]);

    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 250, $this->storeKeeper);

    // Drive a request to the payment gate, unpaid.
    $request = BookRequest::create([
        'request_number'   => BookRequest::nextNumber(),
        'requester_id'     => $this->coordinator->id,
        'destination_type' => RequestDestination::CENTER,
        'center_id'        => $this->center->id,
        'student_count'    => 90,
        'status'           => BookRequestStatus::DRAFT,
    ]);
    $request->items()->create([
        'book_title_id' => $this->title->id, 'quantity_requested' => 90,
        'unit_price' => 150, 'line_total' => 13500,
    ]);
    $request->refresh()->refreshTotals();

    $this->workflow->submit($request->fresh(), $this->coordinator);
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    $this->request = $request->fresh();
});

it('holds the payment gate shut with no money and no deferral', function () {
    expect(fn () => $this->workflow->verifyPayment($this->request, $this->finance))
        ->toThrow(WorkflowException::class);

    expect($this->request->fresh()->status)->toBe(BookRequestStatus::AWAITING_PAYMENT);
});

it('lets finance raise a deferral with a reason', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Centre remits at term end.', now()->addDays(30)->toDateString());

    expect($bypass->reference)->toStartWith('PB-')
        ->and($bypass->status)->toBe(PaymentBypassStatus::PENDING)
        ->and((float) $bypass->amount)->toBe(13500.0)
        ->and($bypass->reason)->toBe('Centre remits at term end.');
});

it('refuses a deferral with an empty reason', function () {
    expect(fn () => $this->bypasses->request($this->request, $this->finance, '   '))
        ->toThrow(WorkflowException::class);
});

it('refuses a deferral from somebody without the grant', function () {
    expect(fn () => $this->bypasses->request($this->request, $this->coordinator, 'Please.'))
        ->toThrow(WorkflowException::class);
});

it('refuses a deferral outside the payment stage', function () {
    $draft = BookRequest::create([
        'request_number'   => BookRequest::nextNumber(),
        'requester_id'     => $this->coordinator->id,
        'destination_type' => RequestDestination::CENTER,
        'center_id'        => $this->center->id,
        'status'           => BookRequestStatus::DRAFT,
    ]);

    expect(fn () => $this->bypasses->request($draft, $this->finance, 'Too early.'))
        ->toThrow(WorkflowException::class);
});

it('refuses a second deferral while one is pending', function () {
    $this->bypasses->request($this->request, $this->finance, 'First.');

    expect(fn () => $this->bypasses->request($this->request->fresh(), $this->finance, 'Second.'))
        ->toThrow(WorkflowException::class);
});

it('will not approve a deferral without a written justification', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Centre remits at term end.');

    expect(fn () => $this->bypasses->approve($bypass, $this->admin, '  '))
        ->toThrow(WorkflowException::class);

    expect($bypass->fresh()->status)->toBe(PaymentBypassStatus::PENDING);
});

it('stops the person who asked for the deferral from authorising it', function () {
    // Finance holds both grants here, so this can only fail on the rule itself.
    $financeWithBoth = bypassActor(['verify_book_payment', 'request_payment_bypass', 'approve_payment_bypass']);

    $bypass = $this->bypasses->request($this->request, $financeWithBoth, 'Centre remits at term end.');

    expect(fn () => $this->bypasses->approve($bypass, $financeWithBoth, 'I vouch for it.'))
        ->toThrow(WorkflowException::class);
});

it('refuses authorisation from somebody without the grant', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Centre remits at term end.');

    expect(fn () => $this->bypasses->approve($bypass, $this->storeKeeper, 'Fine by me.'))
        ->toThrow(WorkflowException::class);
});

it('opens the payment gate once the deferral is authorised', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Centre remits at term end.');
    $this->bypasses->approve($bypass, $this->admin, 'Centre has settled reliably for three years.');

    expect($bypass->fresh()->status)->toBe(PaymentBypassStatus::APPROVED)
        ->and($bypass->fresh()->justification)->toBe('Centre has settled reliably for three years.')
        ->and($this->request->fresh()->paymentGateIsOpen())->toBeTrue();

    $this->workflow->verifyPayment($this->request->fresh(), $this->finance);

    expect($this->request->fresh()->status)->toBe(BookRequestStatus::PAYMENT_VERIFIED);
});

it('records in the trail that the gate opened on a deferral, and under whose authority', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Centre remits at term end.');
    $this->bypasses->approve($bypass, $this->admin, 'Reliable centre.');
    $this->workflow->verifyPayment($this->request->fresh(), $this->finance);

    $paymentStep = $this->request->fresh()->approvals
        ->firstWhere('stage', App\Enums\BookRequestStage::PAYMENT);

    expect($paymentStep->note)->toContain($bypass->reference)
        ->and($paymentStep->note)->toContain($this->admin->name);
});

it('leaves the money owed after the gate opens', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Later.');
    $this->bypasses->approve($bypass, $this->admin, 'Accepted.');
    $this->workflow->verifyPayment($this->request->fresh(), $this->finance);

    expect($this->request->fresh()->outstanding_amount)->toBe(13500.0)
        ->and($bypass->fresh()->status->isOutstandingDebt())->toBeTrue();
});

it('keeps the gate shut when the deferral is rejected', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Later.');
    $this->bypasses->reject($bypass, $this->admin, 'Centre already owes for last term.');

    expect($bypass->fresh()->status)->toBe(PaymentBypassStatus::REJECTED)
        ->and($this->request->fresh()->paymentGateIsOpen())->toBeFalse();

    expect(fn () => $this->workflow->verifyPayment($this->request->fresh(), $this->finance))
        ->toThrow(WorkflowException::class);
});

it('will not decide a deferral twice', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Later.');
    $this->bypasses->approve($bypass, $this->admin, 'Accepted.');

    expect(fn () => $this->bypasses->reject($bypass->fresh(), $this->admin, 'Changed my mind.'))
        ->toThrow(WorkflowException::class);
});

it('settles a deferral when the money finally arrives', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Later.');
    $this->bypasses->approve($bypass, $this->admin, 'Accepted.');

    $this->bypasses->settle($bypass->fresh(), $this->finance);

    expect($bypass->fresh()->status)->toBe(PaymentBypassStatus::SETTLED)
        ->and($bypass->fresh()->settled_by)->toBe($this->finance->id);
});

it('flags a deferral as overdue once the promised date passes', function () {
    $bypass = $this->bypasses->request($this->request, $this->finance, 'Later.', now()->addDay()->toDateString());
    $this->bypasses->approve($bypass, $this->admin, 'Accepted.');

    expect($bypass->fresh()->is_overdue)->toBeFalse();

    $bypass->update(['promised_on' => now()->subDay()]);

    expect($bypass->fresh()->is_overdue)->toBeTrue();
});

it('notifies the authorisers when a deferral is raised', function () {
    Notification::fake();

    $this->bypasses->request($this->request, $this->finance, 'Later.');

    Notification::assertSentTo($this->admin, BookRequestStageChanged::class);
    Notification::assertNotSentTo($this->finance, BookRequestStageChanged::class);
});

it('offers the deferral actions only to the right people', function () {
    $actions = $this->workflow->availableActions($this->request->fresh(), $this->finance);
    expect($actions['request_bypass'])->toBeTrue()
        ->and($actions['decide_bypass'])->toBeFalse();

    $bypass = $this->bypasses->request($this->request, $this->finance, 'Later.');

    $adminActions = $this->workflow->availableActions($this->request->fresh(), $this->admin);
    expect($adminActions['decide_bypass'])->toBeTrue();

    $financeActions = $this->workflow->availableActions($this->request->fresh(), $this->finance);
    expect($financeActions['request_bypass'])->toBeFalse();
});
