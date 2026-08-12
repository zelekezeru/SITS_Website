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
use App\Notifications\BookRequestStageChanged;
use App\Services\Bookstore\BookRequestWorkflow;
use App\Services\Bookstore\StockLedger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

function layerActor(array $permissions): User
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
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am', 'unit_price' => 150,
    ]);

    // One person per layer, exactly as the seeder assigns them.
    $this->coordinator = layerActor(['request_books', 'receive_books']);
    $this->storeKeeper = layerActor(['verify_book_request', 'dispatch_books']);
    $this->finance     = layerActor(['verify_book_payment', 'request_payment_bypass']);
    $this->admin       = layerActor(['approve_book_request', 'approve_payment_bypass']);
    $this->bystander   = layerActor(['view_bookstore']);

    $this->center = Center::create(['name' => 'Halaba', 'code' => 'HLB', 'student_count' => 90]);
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 250, $this->storeKeeper);

    $this->makeRequest = function (): BookRequest {
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

        return $request->fresh();
    };
});

it('tells the store manager when a request is submitted', function () {
    Notification::fake();

    $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    Notification::assertSentTo($this->storeKeeper, BookRequestStageChanged::class);
    Notification::assertNotSentTo($this->finance, BookRequestStageChanged::class);
    Notification::assertNotSentTo($this->bystander, BookRequestStageChanged::class);
});

it('tells finance and the requester when availability is verified', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    Notification::fake();
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    Notification::assertSentTo($this->finance, BookRequestStageChanged::class);
    Notification::assertSentTo($this->coordinator, BookRequestStageChanged::class);
});

it('tells the admin when the payment is verified', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    $request->fresh()->payments()->create([
        'amount' => 13500, 'method' => BookPaymentMethod::CASH,
        'paid_on' => now()->toDateString(), 'status' => BookPaymentStatus::VERIFIED,
        'recorded_by' => $this->finance->id, 'verified_by' => $this->finance->id, 'verified_at' => now(),
    ]);

    Notification::fake();
    $this->workflow->verifyPayment($request->fresh(), $this->finance);

    Notification::assertSentTo($this->admin, BookRequestStageChanged::class);
});

it('tells the store to dispatch once the admin approves', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    $request->fresh()->payments()->create([
        'amount' => 13500, 'method' => BookPaymentMethod::CASH,
        'paid_on' => now()->toDateString(), 'status' => BookPaymentStatus::VERIFIED,
        'recorded_by' => $this->finance->id, 'verified_by' => $this->finance->id, 'verified_at' => now(),
    ]);
    $this->workflow->verifyPayment($request->fresh(), $this->finance);

    Notification::fake();
    $this->workflow->approve($request->fresh(), $this->admin);

    Notification::assertSentTo($this->storeKeeper, BookRequestStageChanged::class);
    Notification::assertSentTo($this->coordinator, BookRequestStageChanged::class);
});

it('never notifies the person who performed the action', function () {
    Notification::fake();

    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    // The store keeper acted, so they are not told about their own verification.
    Notification::assertSentToTimes($this->storeKeeper, BookRequestStageChanged::class, 1);
});

it('tells the requester when a request is rejected', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    Notification::fake();
    $this->workflow->reject($request->fresh(), $this->storeKeeper, BookRequestStage::VERIFICATION, 'Duplicate.');

    Notification::assertSentTo($this->coordinator, BookRequestStageChanged::class);
});

it('follows the permission, not the person — regranting redirects the alerts', function () {
    $newStoreKeeper = layerActor(['verify_book_request']);

    Notification::fake();
    $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    // Both holders of verify_book_request are told, with no list to maintain.
    Notification::assertSentTo($this->storeKeeper, BookRequestStageChanged::class);
    Notification::assertSentTo($newStoreKeeper, BookRequestStageChanged::class);
});

it('carries the request number and a link in the notification payload', function () {
    Notification::fake();

    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    Notification::assertSentTo($this->storeKeeper, BookRequestStageChanged::class,
        function (BookRequestStageChanged $notification) use ($request) {
            $payload = $notification->toArray($this->storeKeeper);

            return $payload['request_number'] === $request->request_number
                && str_contains($payload['url'], "/bookstore/requests/{$request->id}")
                && $payload['title'] !== '';
        });
});

// ── Stage timing ────────────────────────────────────────────────────────────

it('stamps every step with when it happened and how long it waited', function () {
    $request = ($this->makeRequest)();
    $this->travel(2)->hours();
    $this->workflow->submit($request->fresh(), $this->coordinator);
    $this->travel(5)->hours();
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    $trail = $request->fresh()->approvals;

    expect($trail)->toHaveCount(2)
        ->and($trail[0]->acted_at)->not->toBeNull()
        ->and($trail[0]->waited_seconds)->toBeGreaterThanOrEqual(7100)   // ~2h in the draft
        ->and($trail[1]->waited_seconds)->toBeGreaterThanOrEqual(17900); // ~5h awaiting verification

    $this->travelBack();
});

it('reports how long the current stage has been waiting', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    $this->travel(3)->days();

    $fresh = $request->fresh()->load('approvals');

    expect($fresh->current_stage_age)->toBeGreaterThanOrEqual(3 * 86400 - 60)
        ->and($fresh->status->awaitingDescription())->toBe('Store manager to check availability');

    $this->travelBack();
});

it('names who owes the next action at each stage', function () {
    $request = ($this->makeRequest)();

    expect($request->status->awaitingPermission()->value)->toBe('request_books');

    $this->workflow->submit($request->fresh(), $this->coordinator);
    expect($request->fresh()->status->awaitingPermission()->value)->toBe('verify_book_request');

    $this->workflow->verify($request->fresh(), $this->storeKeeper);
    expect($request->fresh()->status->awaitingPermission()->value)->toBe('verify_book_payment');
});

it('reports no waiting owner once the request is closed', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);
    $this->workflow->reject($request->fresh(), $this->storeKeeper, BookRequestStage::VERIFICATION, 'No.');

    $fresh = $request->fresh();

    expect($fresh->status->awaitingPermission())->toBeNull()
        ->and($fresh->currentStageEnteredAt())->toBeNull()
        ->and($fresh->status->awaitingDescription())->toBe('Nothing — this request is closed');
});

// ── Transactional integrity of the notification hand-off ────────────────────

it('sends nothing when the transition itself fails and rolls back', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    // A second line makes the failure happen *after* the first has already been
    // reserved, so this exercises a genuine mid-transaction rollback.
    $second = BookTitle::create([
        'code' => 'XX-01', 'title' => 'Hermeneutics', 'language' => 'am', 'unit_price' => 100,
    ]);
    $this->ledger->post($second, $this->section, StockMovementType::RECEIPT, 50, $this->storeKeeper);
    $request->items()->create([
        'book_title_id' => $second->id, 'quantity_requested' => 10,
        'unit_price' => 100, 'line_total' => 1000,
    ]);

    $items = $request->fresh()->items;

    Notification::fake();

    // Over-approving the second line throws once the first is already reserved.
    expect(fn () => $this->workflow->verify($request->fresh(), $this->storeKeeper, [
        $items[0]->id => 90,
        $items[1]->id => 999,
    ]))->toThrow(App\Services\Bookstore\WorkflowException::class);

    Notification::assertNothingSent();

    // And the reservation from the first line was rolled back with it.
    expect($this->title->fresh()->total_reserved)->toBe(0)
        ->and($request->fresh()->status)->toBe(BookRequestStatus::SUBMITTED);
});

it('completes the transition even when the mail host is down', function () {
    $request = $this->workflow->submit(($this->makeRequest)(), $this->coordinator);

    // A dead mail host is an operational problem, not a reason to fail the step
    // the user just completed.
    Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP unreachable'));
    Log::shouldReceive('error')->once();

    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    expect($request->fresh()->status)->toBe(BookRequestStatus::AWAITING_PAYMENT)
        ->and($this->title->fresh()->total_reserved)->toBe(90);
});

it('tags notifications with an icon bucket the shared bell understands', function () {
    // The bell keys its glyph off data.category; an unknown value silently
    // degrades to a generic bell, which is easy to ship and hard to notice.
    $known = ['clock', 'alert', 'check', 'cash', 'truck', 'bell'];

    foreach (App\Enums\BookRequestEvent::cases() as $event) {
        expect($known)->toContain($event->bellCategory());
    }
});
