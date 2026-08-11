<?php

use App\Enums\BookDispatchStatus;
use App\Enums\BookPaymentMethod;
use App\Enums\BookPaymentStatus;
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
use App\Services\Bookstore\DispatchService;
use App\Services\Bookstore\ReturnService;
use App\Services\Bookstore\StockLedger;
use App\Services\Bookstore\WorkflowException;
use Spatie\Permission\Models\Permission;

function dispatchActor(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission]);
        $user->givePermissionTo($permission);
    }

    return $user;
}

beforeEach(function () {
    $this->ledger     = app(StockLedger::class);
    $this->workflow   = app(BookRequestWorkflow::class);
    $this->dispatcher = app(DispatchService::class);
    $this->returns    = app(ReturnService::class);

    $store         = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $store->id, 'code' => 'A']);
    $this->store   = $store;
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02']);

    $this->title = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am', 'unit_price' => 150,
    ]);

    $this->coordinator = dispatchActor(['request_books', 'receive_books']);
    $this->verifier    = dispatchActor(['verify_book_request']);
    $this->finance     = dispatchActor(['verify_book_payment']);
    $this->approver    = dispatchActor(['approve_book_request']);
    $this->storeKeeper = dispatchActor(['dispatch_books', 'record_book_return']);

    $this->center = Center::create(['name' => 'Halaba', 'code' => 'HLB', 'student_count' => 90]);

    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 250, $this->storeKeeper);

    // Drive one request all the way to APPROVED, ready to be dispatched.
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
    $this->workflow->verify($request->fresh(), $this->verifier);

    $request->fresh()->payments()->create([
        'amount' => 13500, 'method' => BookPaymentMethod::BANK_TRANSFER,
        'transaction_reference' => 'FT1', 'crv_number' => 'CRV-1',
        'paid_on' => now()->toDateString(), 'status' => BookPaymentStatus::VERIFIED,
        'recorded_by' => $this->coordinator->id, 'verified_by' => $this->finance->id,
        'verified_at' => now(),
    ]);

    $this->workflow->verifyPayment($request->fresh(), $this->finance);
    $this->workflow->approve($request->fresh(), $this->approver);

    $this->request = $request->fresh();
    $this->item    = $this->request->items()->first();
});

it('deducts stock and consumes the reservation on dispatch', function () {
    $dispatch = $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);

    expect($dispatch->dispatch_number)->toStartWith('BD-')
        ->and($dispatch->total_quantity)->toBe(90)
        ->and((float) $dispatch->total_amount)->toBe(13500.0)
        ->and($this->title->fresh()->total_on_hand)->toBe(160)
        ->and($this->title->fresh()->total_reserved)->toBe(0)
        ->and($this->request->fresh()->status)->toBe(BookRequestStatus::DISPATCHED);
});

it('keeps a partially dispatched request open for the balance', function () {
    $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 50],
    ]);

    expect($this->request->fresh()->status)->toBe(BookRequestStatus::PARTIALLY_DISPATCHED)
        ->and($this->item->fresh()->quantity_dispatched)->toBe(50)
        ->and($this->item->fresh()->quantity_outstanding)->toBe(40)
        ->and($this->title->fresh()->total_reserved)->toBe(40);

    $this->dispatcher->dispatch($this->request->fresh(), $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 40],
    ]);

    expect($this->request->fresh()->status)->toBe(BookRequestStatus::DISPATCHED)
        ->and($this->title->fresh()->total_on_hand)->toBe(160);
});

it('refuses to dispatch more than the request still owes', function () {
    expect(fn () => $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 91],
    ]))->toThrow(WorkflowException::class);

    expect($this->title->fresh()->total_on_hand)->toBe(250);
});

it('refuses to dispatch a request that has not been approved', function () {
    $draft = BookRequest::create([
        'request_number'   => BookRequest::nextNumber(),
        'requester_id'     => $this->coordinator->id,
        'destination_type' => RequestDestination::CENTER,
        'center_id'        => $this->center->id,
        'status'           => BookRequestStatus::DRAFT,
    ]);

    expect(fn () => $this->dispatcher->dispatch($draft, $this->storeKeeper, [
        ['book_request_item_id' => 1, 'shelf_section_id' => $this->section->id, 'quantity' => 1],
    ]))->toThrow(WorkflowException::class);
});

it('refuses a dispatch with nothing on it', function () {
    expect(fn () => $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 0],
    ]))->toThrow(WorkflowException::class);
});

it('closes the request when the receiver confirms the last consignment', function () {
    $dispatch = $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);

    $this->dispatcher->confirmReceipt($dispatch, $this->coordinator);

    expect($dispatch->fresh()->status)->toBe(BookDispatchStatus::RECEIVED)
        ->and($this->request->fresh()->status)->toBe(BookRequestStatus::RECEIVED);
});

it('is idempotent when a receipt is confirmed twice', function () {
    $dispatch = $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);

    $this->dispatcher->confirmReceipt($dispatch, $this->coordinator);
    $first = $dispatch->fresh()->received_at;

    $this->dispatcher->confirmReceipt($dispatch->fresh(), $this->coordinator);

    expect($dispatch->fresh()->received_at->eq($first))->toBeTrue();
});

it('re-shelves returned copies and writes off the damaged ones', function () {
    $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);

    $return = $this->returns->record([
        'center_id'        => $this->center->id,
        'shelf_section_id' => $this->section->id,
        'returned_on'      => now()->toDateString(),
    ], [
        ['book_title_id' => $this->title->id, 'quantity_returned' => 12, 'quantity_damaged' => 2],
    ], $this->storeKeeper);

    // 160 left + 12 back - 2 written off = 170.
    expect($this->title->fresh()->total_on_hand)->toBe(170)
        ->and($return->total_quantity)->toBe(12)
        ->and($return->items->first()->quantity_resaleable)->toBe(10);
});

it('refuses a return whose damaged count exceeds the returned count', function () {
    expect(fn () => $this->returns->record([
        'center_id'        => $this->center->id,
        'shelf_section_id' => $this->section->id,
        'returned_on'      => now()->toDateString(),
    ], [
        ['book_title_id' => $this->title->id, 'quantity_returned' => 5, 'quantity_damaged' => 6],
    ], $this->storeKeeper))->toThrow(WorkflowException::class);
});

it('reconciles issued against returned for a centre', function () {
    $this->dispatcher->dispatch($this->request, $this->storeKeeper, [
        ['book_request_item_id' => $this->item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);

    $this->returns->record([
        'center_id'        => $this->center->id,
        'shelf_section_id' => $this->section->id,
        'returned_on'      => now()->toDateString(),
    ], [
        ['book_title_id' => $this->title->id, 'quantity_returned' => 12],
    ], $this->storeKeeper);

    $row = $this->returns->outstandingForCenter($this->center)->first();

    expect((int) $row->issued)->toBe(90)
        ->and((int) $row->returned)->toBe(12)
        ->and((int) $row->outstanding)->toBe(78);
});
