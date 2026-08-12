<?php

use App\Enums\BookPaymentMethod;
use App\Enums\BookPaymentStatus;
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
use App\Services\Bookstore\BookRequestWorkflow;
use App\Services\Bookstore\DispatchService;
use App\Services\Bookstore\PaymentBypassService;
use App\Services\Bookstore\StockLedger;
use Database\Seeders\BookstorePermissionsSeeder;
use Spatie\Permission\Models\Role;

/**
 * The whole journey, driven by people holding exactly the roles the seeder
 * grants — not hand-picked permissions. If the seeder ever hands the wrong
 * layer to the wrong role, these break.
 */
beforeEach(function () {
    (new BookstorePermissionsSeeder)->run();

    $this->ledger     = app(StockLedger::class);
    $this->workflow   = app(BookRequestWorkflow::class);
    $this->dispatcher = app(DispatchService::class);
    $this->bypasses   = app(PaymentBypassService::class);

    $asRole = function (string $role): User {
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', $role)->firstOrFail());

        return $user;
    };

    $this->coordinator = $asRole('Center Coordinator');
    $this->storeKeeper = $asRole('Store Manager');
    $this->finance     = $asRole('Finance Officer');
    $this->admin       = $asRole('Bookstore Approver');

    $store         = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $store->id, 'code' => 'A']);
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02']);

    $this->title = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am', 'unit_price' => 150,
    ]);

    $this->center = Center::create(['name' => 'Halaba', 'code' => 'HLB', 'student_count' => 90]);
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 250, $this->storeKeeper);

    $this->raise = function (int $quantity = 90): BookRequest {
        $request = BookRequest::create([
            'request_number'   => BookRequest::nextNumber(),
            'requester_id'     => $this->coordinator->id,
            'destination_type' => RequestDestination::CENTER,
            'center_id'        => $this->center->id,
            'student_count'    => 90,
            'status'           => BookRequestStatus::DRAFT,
        ]);
        $request->items()->create([
            'book_title_id' => $this->title->id, 'quantity_requested' => $quantity,
            'unit_price' => 150, 'line_total' => $quantity * 150,
        ]);
        $request->refresh()->refreshTotals();

        return $request->fresh();
    };
});

it('walks a paid request from request to receipt with the seeded roles', function () {
    $request = ($this->raise)();

    // 1. Coordinator raises it.
    $this->workflow->submit($request, $this->coordinator);
    expect($request->fresh()->status)->toBe(BookRequestStatus::SUBMITTED);

    // 2. Store manager checks the shelves — stock is reserved, not deducted.
    $this->workflow->verify($request->fresh(), $this->storeKeeper);
    expect($request->fresh()->status)->toBe(BookRequestStatus::AWAITING_PAYMENT)
        ->and($this->title->fresh()->total_on_hand)->toBe(250)
        ->and($this->title->fresh()->total_reserved)->toBe(90);

    // 3. The centre pays; finance confirms receipt of the money.
    $payment = $request->fresh()->payments()->create([
        'amount' => 13500, 'method' => BookPaymentMethod::BANK_TRANSFER,
        'bank_name' => 'CBE', 'transaction_reference' => 'FT2603XY', 'crv_number' => 'CRV-00871',
        'paid_on' => now()->toDateString(), 'status' => BookPaymentStatus::PENDING,
        'recorded_by' => $this->coordinator->id,
    ]);
    $payment->verify($this->finance);
    $this->workflow->verifyPayment($request->fresh(), $this->finance);
    expect($request->fresh()->status)->toBe(BookRequestStatus::PAYMENT_VERIFIED);

    // 4. Admin gives final approval.
    $this->workflow->approve($request->fresh(), $this->admin);
    expect($request->fresh()->status)->toBe(BookRequestStatus::APPROVED);

    // 5. Store dispatches — now the stock actually leaves.
    $item     = $request->fresh()->items()->first();
    $dispatch = $this->dispatcher->dispatch($request->fresh(), $this->storeKeeper, [
        ['book_request_item_id' => $item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);
    expect($request->fresh()->status)->toBe(BookRequestStatus::DISPATCHED)
        ->and($this->title->fresh()->total_on_hand)->toBe(160)
        ->and($this->title->fresh()->total_reserved)->toBe(0);

    // 6. Receiver confirms; the request closes.
    $this->dispatcher->confirmReceipt($dispatch, $this->coordinator);
    expect($request->fresh()->status)->toBe(BookRequestStatus::RECEIVED);

    // Every layer signed, in order, each with a timestamp and a dwell time.
    $trail = $request->fresh()->approvals;
    expect($trail->pluck('stage')->map->value->all())
        ->toBe(['submission', 'verification', 'payment', 'approval', 'dispatch', 'receipt']);

    expect($trail->every(fn ($a) => $a->acted_at !== null && $a->waited_seconds !== null))->toBeTrue();

    // Four different people, so no one walked it through alone.
    expect($trail->pluck('actor_id')->unique()->count())->toBeGreaterThanOrEqual(4);
});

it('walks an unpaid request through on an authorised deferral', function () {
    $request = ($this->raise)();

    $this->workflow->submit($request, $this->coordinator);
    $this->workflow->verify($request->fresh(), $this->storeKeeper);

    // No money — finance asks to defer, the admin accepts the debt.
    $bypass = $this->bypasses->request($request->fresh(), $this->finance, 'Centre remits at term end.');
    $this->bypasses->approve($bypass, $this->admin, 'Centre has settled reliably for three years.');

    $this->workflow->verifyPayment($request->fresh(), $this->finance);
    $this->workflow->approve($request->fresh(), $this->admin);

    $item = $request->fresh()->items()->first();
    $this->dispatcher->dispatch($request->fresh(), $this->storeKeeper, [
        ['book_request_item_id' => $item->id, 'shelf_section_id' => $this->section->id, 'quantity' => 90],
    ]);

    // Books have left, and the money is still owed and still visible.
    expect($request->fresh()->status)->toBe(BookRequestStatus::DISPATCHED)
        ->and($this->title->fresh()->total_on_hand)->toBe(160)
        ->and($request->fresh()->outstanding_amount)->toBe(13500.0)
        ->and($bypass->fresh()->status)->toBe(PaymentBypassStatus::APPROVED);
});

it('gives no single seeded role the power to walk a request through alone', function () {
    // The four grants that move a request forward must not collect on one role.
    $gates = ['verify_book_request', 'verify_book_payment', 'approve_book_request'];

    foreach (['Store Manager', 'Finance Officer', 'Bookstore Approver', 'Center Coordinator', 'Bookstore Admin'] as $name) {
        $role = Role::where('name', $name)->firstOrFail();
        $held = collect($gates)->filter(fn ($g) => $role->hasPermissionTo($g));

        expect($held->count())->toBeLessThan(2,
            "Role [{$name}] holds more than one workflow gate: ".$held->join(', '));
    }
});

it('never lets the same role both request and authorise a deferral', function () {
    foreach (Role::all() as $role) {
        if ($role->hasPermissionTo('request_payment_bypass') && $role->hasPermissionTo('approve_payment_bypass')) {
            // Super admin legitimately holds everything; the service still
            // blocks the same *person* doing both.
            expect($role->name)->toContain('Super Admin');
        }
    }
})->skip(fn () => Role::whereIn('name', ['Super Admin', 'President / Super Admin'])->doesntExist(),
    'no super-admin role seeded in this environment');

it('always seeds an approval holder, with no dependency on the ERP seeder', function () {
    // Run in isolation above; if approve_book_request had no holder, every
    // request would deadlock at the approval gate.
    expect(Role::where('name', 'Bookstore Approver')->firstOrFail()->hasPermissionTo('approve_book_request'))->toBeTrue()
        ->and(Role::where('name', 'Bookstore Approver')->firstOrFail()->hasPermissionTo('approve_payment_bypass'))->toBeTrue();
});

it('puts the store manager, not the bookstore admin, on the availability check', function () {
    expect(Role::where('name', 'Store Manager')->firstOrFail()->hasPermissionTo('verify_book_request'))->toBeTrue()
        ->and(Role::where('name', 'Bookstore Admin')->firstOrFail()->hasPermissionTo('verify_book_request'))->toBeFalse();
});

it('keeps finance away from dispatch and approval', function () {
    $finance = Role::where('name', 'Finance Officer')->firstOrFail();

    expect($finance->hasPermissionTo('dispatch_books'))->toBeFalse()
        ->and($finance->hasPermissionTo('approve_book_request'))->toBeFalse()
        ->and($finance->hasPermissionTo('approve_payment_bypass'))->toBeFalse();
});
