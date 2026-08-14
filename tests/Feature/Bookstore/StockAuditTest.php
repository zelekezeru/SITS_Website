<?php

use App\Enums\StockAuditStatus;
use App\Enums\StockMovementType;
use App\Models\BookTitle;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StockMovement;
use App\Models\StoreRoom;
use App\Models\User;
use App\Services\Bookstore\StockAuditService;
use App\Services\Bookstore\StockLedger;
use App\Services\Bookstore\WorkflowException;

beforeEach(function () {
    $this->ledger = app(StockLedger::class);
    $this->audits = app(StockAuditService::class);

    $this->counter  = User::factory()->create();
    $this->approver = User::factory()->create();

    $this->store   = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $this->store->id, 'code' => 'A']);
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02']);

    $this->title = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am', 'unit_price' => 150,
    ]);

    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 170, $this->counter);
});

it('freezes the expected quantity when the audit starts', function () {
    $audit = $this->audits->start($this->store, $this->counter);

    expect($audit->status)->toBe(StockAuditStatus::IN_PROGRESS)
        ->and($audit->lines)->toHaveCount(1)
        ->and($audit->lines->first()->system_quantity)->toBe(170);

    // Stock moving mid-count must not disturb the frozen snapshot.
    $this->ledger->post($this->title->fresh(), $this->section, StockMovementType::ISSUE, 20, $this->counter);

    expect($audit->fresh()->lines->first()->system_quantity)->toBe(170);
});

it('does not touch stock before the variance is approved', function () {
    $audit = $this->audits->start($this->store, $this->counter);
    $line  = $audit->lines->first();

    $this->audits->count($line, 168, $this->counter, 'two missing');
    $this->audits->complete($audit->fresh(), $this->counter);

    expect($audit->fresh()->status)->toBe(StockAuditStatus::COMPLETED)
        ->and($line->fresh()->variance)->toBe(-2)
        ->and($this->title->fresh()->total_on_hand)->toBe(170);
});

it('posts a shortage correction only once the audit is approved', function () {
    $audit = $this->audits->start($this->store, $this->counter);
    $this->audits->count($audit->lines->first(), 168, $this->counter);
    $this->audits->complete($audit->fresh(), $this->counter);

    $this->audits->approve($audit->fresh(), $this->approver);

    expect($audit->fresh()->status)->toBe(StockAuditStatus::APPROVED)
        ->and($this->title->fresh()->total_on_hand)->toBe(168)
        ->and($this->ledger->recomputeBalance($this->title->id, $this->section->id))->toBe(168)
        ->and(StockMovement::where('type', StockMovementType::AUDIT_SHORTAGE->value)->count())->toBe(1);
});

it('posts a surplus correction when more is found than expected', function () {
    $audit = $this->audits->start($this->store, $this->counter);
    $this->audits->count($audit->lines->first(), 175, $this->counter);
    $this->audits->complete($audit->fresh(), $this->counter);
    $this->audits->approve($audit->fresh(), $this->approver);

    expect($this->title->fresh()->total_on_hand)->toBe(175)
        ->and(StockMovement::where('type', StockMovementType::AUDIT_SURPLUS->value)->count())->toBe(1);
});

it('posts nothing when everything counted matches', function () {
    $audit = $this->audits->start($this->store, $this->counter);
    $this->audits->count($audit->lines->first(), 170, $this->counter);
    $this->audits->complete($audit->fresh(), $this->counter);

    $before = StockMovement::count();
    $this->audits->approve($audit->fresh(), $this->approver);

    expect(StockMovement::count())->toBe($before)
        ->and($this->title->fresh()->total_on_hand)->toBe(170);
});

it('will not complete an audit with an uncounted line', function () {
    $audit = $this->audits->start($this->store, $this->counter);

    expect(fn () => $this->audits->complete($audit, $this->counter))
        ->toThrow(WorkflowException::class);
});

it('will not approve an audit that is still being counted', function () {
    $audit = $this->audits->start($this->store, $this->counter);
    $this->audits->count($audit->lines->first(), 170, $this->counter);

    expect(fn () => $this->audits->approve($audit->fresh(), $this->approver))
        ->toThrow(WorkflowException::class);
});

it('refuses a count once the audit is closed', function () {
    $audit = $this->audits->start($this->store, $this->counter);
    $line  = $audit->lines->first();

    $this->audits->count($line, 170, $this->counter);
    $this->audits->complete($audit->fresh(), $this->counter);

    expect(fn () => $this->audits->count($line->fresh(), 165, $this->counter))
        ->toThrow(WorkflowException::class);
});

it('lets a counter add a title found where it was not expected', function () {
    $other = BookTitle::create([
        'code' => 'XX-01', 'title' => 'Strays', 'language' => 'am', 'unit_price' => 100,
    ]);

    $audit = $this->audits->start($this->store, $this->counter);
    $line  = $this->audits->addLine($audit, $this->section->id, $other->id);

    expect($line->system_quantity)->toBe(0);

    $this->audits->count($line, 7, $this->counter);
    $this->audits->count($audit->fresh()->lines()->where('book_title_id', $this->title->id)->first(), 170, $this->counter);
    $this->audits->complete($audit->fresh(), $this->counter);
    $this->audits->approve($audit->fresh(), $this->approver);

    expect($other->fresh()->total_on_hand)->toBe(7);
});
