<?php

use App\Enums\StockMovementType;
use App\Models\BookTitle;
use App\Models\Center;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StoreRoom;
use App\Models\StudyMode;
use App\Models\User;
use App\Services\Bookstore\QrLabelService;
use App\Services\Bookstore\StockLedger;
use Spatie\Permission\Models\Permission;

function bookstoreUser(array $permissions = ['view_bookstore']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission]);
        $user->givePermissionTo($permission);
    }

    return $user;
}

beforeEach(function () {
    $store         = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $store->id, 'code' => 'A', 'label' => 'Shelf A']);
    $this->store   = $store;
    $this->shelf   = $shelf;
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02', 'name' => 'Sociology']);

    $this->title = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am',
        'unit_price' => 150, 'reorder_level' => 20,
    ]);

    Center::create(['name' => 'Halaba', 'code' => 'HLB', 'student_count' => 90]);
    StudyMode::create(['name' => 'Distance', 'code' => 'DST']);

    app(StockLedger::class)->post(
        $this->title,
        $this->section,
        StockMovementType::RECEIPT,
        250,
        bookstoreUser(),
    );
});

it('turns guests away from the whole module', function () {
    $this->get(route('bookstore.dashboard'))->assertRedirect(route('login'));
});

it('refuses a signed-in user without the bookstore permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('bookstore.dashboard'))
        ->assertForbidden();
});

it('renders every read-only screen for a viewer', function (string $name, array $params) {
    $this->actingAs(bookstoreUser())
        ->get(route($name, $params))
        ->assertOk();
})->with(fn () => [
    'dashboard'  => ['bookstore.dashboard', []],
    'titles'     => ['bookstore.titles.index', []],
    'stock'      => ['bookstore.stock.index', []],
    'low stock'  => ['bookstore.stock.low', []],
    'stores'     => ['bookstore.stores.index', []],
    'centres'    => ['bookstore.centers.index', []],
    'requests'   => ['bookstore.requests.index', []],
    'dispatches' => ['bookstore.dispatches.index', []],
    'returns'    => ['bookstore.returns.index', []],
    'scan'       => ['bookstore.scan.index', []],
]);

it('renders a title, its bin card, and the section it sits on', function () {
    $user = bookstoreUser();

    $this->actingAs($user)->get(route('bookstore.titles.show', $this->title))->assertOk();
    $this->actingAs($user)->get(route('bookstore.stock.bin-card', $this->title))->assertOk();
    $this->actingAs($user)->get(route('bookstore.sections.show', $this->section))->assertOk();
    $this->actingAs($user)->get(route('bookstore.stores.show', $this->store))->assertOk();
});

it('keeps catalogue editing behind its own permission', function () {
    $viewer = bookstoreUser();

    $this->actingAs($viewer)->get(route('bookstore.titles.create'))->assertForbidden();
    $this->actingAs($viewer)->get(route('bookstore.study-modes.index'))->assertForbidden();

    $editor = bookstoreUser(['view_bookstore', 'manage_book_titles']);

    $this->actingAs($editor)->get(route('bookstore.titles.create'))->assertOk();
    $this->actingAs($editor)->get(route('bookstore.study-modes.index'))->assertOk();
});

it('keeps reports, audits and payments behind their own permissions', function () {
    $viewer = bookstoreUser();

    $this->actingAs($viewer)->get(route('bookstore.reports.index'))->assertForbidden();
    $this->actingAs($viewer)->get(route('bookstore.audits.index'))->assertForbidden();
    $this->actingAs($viewer)->get(route('bookstore.payments.index'))->assertForbidden();

    $this->actingAs(bookstoreUser(['view_bookstore', 'view_book_reports']))
        ->get(route('bookstore.reports.index'))->assertOk();
    $this->actingAs(bookstoreUser(['view_bookstore', 'conduct_stock_audit']))
        ->get(route('bookstore.audits.index'))->assertOk();
    $this->actingAs(bookstoreUser(['view_bookstore', 'verify_book_payment']))
        ->get(route('bookstore.payments.index'))->assertOk();
});

it('builds every report without error', function (string $report) {
    $this->actingAs(bookstoreUser(['view_bookstore', 'view_book_reports']))
        ->get(route('bookstore.reports.index', ['report' => $report]))
        ->assertOk();
})->with([
    'stock_on_hand',
    'movement_summary',
    'distribution',
    'outstanding_returns',
    'request_pipeline',
    'payments',
    'audit_variance',
    'reprint_forecast',
]);

it('exports a report as CSV', function () {
    $this->actingAs(bookstoreUser(['view_bookstore', 'view_book_reports']))
        ->get(route('bookstore.reports.export', ['report' => 'stock_on_hand']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('rejects an unknown report name', function () {
    $this->actingAs(bookstoreUser(['view_bookstore', 'view_book_reports']))
        ->get(route('bookstore.reports.index', ['report' => 'not_a_report']))
        ->assertNotFound();
});

it('serves a QR image for every scannable type', function (string $type, string $model) {
    $id = match ($type) {
        'title'   => $this->title->id,
        'store'   => $this->store->id,
        'shelf'   => $this->shelf->id,
        'section' => $this->section->id,
    };

    $response = $this->actingAs(bookstoreUser())->get(route('bookstore.labels.png', ['type' => $type, 'id' => $id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain(
        app(QrLabelService::class)->supportsPng() ? 'image/png' : 'image/svg'
    );
})->with([
    ['title', BookTitle::class],
    ['store', StoreRoom::class],
    ['shelf', Shelf::class],
    ['section', ShelfSection::class],
]);

it('404s a QR request for an unknown type', function () {
    $this->actingAs(bookstoreUser())
        ->get(route('bookstore.labels.png', ['type' => 'nonsense', 'id' => 1]))
        ->assertNotFound();
});

it('sends a scanned section hash to that section', function () {
    $this->actingAs(bookstoreUser())
        ->get(route('bookstore.scan.resolve', $this->section->tracking_hash))
        ->assertRedirect(route('bookstore.sections.show', $this->section));
});

it('sends a scanned title hash to that title', function () {
    $this->actingAs(bookstoreUser())
        ->get(route('bookstore.scan.resolve', $this->title->tracking_hash))
        ->assertRedirect(route('bookstore.titles.show', $this->title));
});

it('sends an unknown hash back to the scanner with a message', function () {
    $this->actingAs(bookstoreUser())
        ->get(route('bookstore.scan.resolve', '00000000-0000-0000-0000-000000000000'))
        ->assertRedirect(route('bookstore.scan.index'))
        ->assertSessionHas('error');
});

it('answers the scanner lookup with the model behind the code', function () {
    $response = $this->actingAs(bookstoreUser())
        ->postJson(route('bookstore.scan.lookup'), ['code' => $this->section->tracking_hash]);

    $response->assertOk()->assertJson(['found' => true, 'type' => 'section']);
});

it('accepts a full scanned URL as well as a bare hash', function () {
    $this->actingAs(bookstoreUser())
        ->postJson(route('bookstore.scan.lookup'), [
            'code' => route('bookstore.scan.resolve', $this->title->tracking_hash),
        ])
        ->assertOk()
        ->assertJson(['found' => true, 'type' => 'title']);
});

it('reports nothing found for a code that matches no model', function () {
    $this->actingAs(bookstoreUser())
        ->postJson(route('bookstore.scan.lookup'), ['code' => 'rubbish'])
        ->assertNotFound()
        ->assertJson(['found' => false]);
});

it('records a print run and brings the stock in', function () {
    $user = bookstoreUser(['view_bookstore', 'manage_print_runs']);

    $this->actingAs($user)->post(route('bookstore.print-runs.store'), [
        'book_title_id'    => $this->title->id,
        'quantity'         => 100,
        'unit_cost'        => 80,
        'received_on'      => now()->toDateString(),
        'shelf_section_id' => $this->section->id,
    ])->assertRedirect();

    expect($this->title->fresh()->total_on_hand)->toBe(350);
});

it('will not archive a title that still has stock on the shelf', function () {
    $this->actingAs(bookstoreUser(['view_bookstore', 'manage_book_titles']))
        ->delete(route('bookstore.titles.destroy', $this->title))
        ->assertRedirect();

    expect(BookTitle::find($this->title->id))->not->toBeNull();
});
