<?php

use App\Enums\StockMovementType;
use App\Models\BookStock;
use App\Models\BookTitle;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StoreRoom;
use App\Models\User;
use App\Services\Bookstore\StockLedger;

beforeEach(function () {
    $this->ledger  = app(StockLedger::class);
    $this->user    = User::factory()->create();
    $store         = StoreRoom::create(['name' => 'Main Store', 'code' => 'MS']);
    $shelf         = Shelf::create(['store_room_id' => $store->id, 'code' => 'A', 'label' => 'Shelf A']);
    $this->store   = $store;
    $this->section = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-02', 'name' => 'Sociology']);
    $this->other   = ShelfSection::create(['shelf_id' => $shelf->id, 'code' => 'SM-03']);
    $this->title   = BookTitle::create([
        'code' => 'SM-02', 'title' => 'Sociology', 'language' => 'am',
        'unit_price' => 150, 'reorder_level' => 20,
    ]);
});

it('posts a receipt and records the resulting balance on the bin card', function () {
    $movement = $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 250, $this->user, [
        'unit_price' => 90,
    ]);

    expect($movement->balance_after)->toBe(250)
        ->and($movement->total_price)->toEqual('22500.00')
        ->and($this->title->fresh()->total_on_hand)->toBe(250);
});

it('refuses to issue more than the section holds', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 10, $this->user);

    expect(fn () => $this->ledger->post($this->title, $this->section, StockMovementType::ISSUE, 11, $this->user))
        ->toThrow(RuntimeException::class);

    expect($this->title->fresh()->total_on_hand)->toBe(10);
});

it('rejects a zero or negative quantity', function () {
    expect(fn () => $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 0, $this->user))
        ->toThrow(RuntimeException::class);
});

it('keeps the cached balance in step with a replay of the ledger', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 100, $this->user);
    $this->ledger->post($this->title, $this->section, StockMovementType::ISSUE, 30, $this->user);
    $this->ledger->post($this->title, $this->section, StockMovementType::RETURN_IN, 5, $this->user);
    $this->ledger->post($this->title, $this->section, StockMovementType::DAMAGE, 2, $this->user);

    $cached = (int) BookStock::where('book_title_id', $this->title->id)
        ->where('shelf_section_id', $this->section->id)
        ->value('quantity');

    expect($cached)->toBe(73)
        ->and($this->ledger->recomputeBalance($this->title->id, $this->section->id))->toBe(73);
});

it('moves stock between sections as a matched out and in pair', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 100, $this->user);

    [$out, $in] = $this->ledger->transfer($this->title, $this->section, $this->other, 40, $this->user);

    expect($out->type)->toBe(StockMovementType::TRANSFER_OUT)
        ->and($in->type)->toBe(StockMovementType::TRANSFER_IN)
        ->and($this->ledger->recomputeBalance($this->title->id, $this->section->id))->toBe(60)
        ->and($this->ledger->recomputeBalance($this->title->id, $this->other->id))->toBe(40)
        ->and($this->title->fresh()->total_on_hand)->toBe(100);
});

it('refuses a transfer to the same section', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 10, $this->user);

    expect(fn () => $this->ledger->transfer($this->title, $this->section, $this->section, 1, $this->user))
        ->toThrow(RuntimeException::class);
});

it('holds reserved copies back from availability without moving them', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 100, $this->user);

    $this->ledger->reserve($this->title, 30);

    expect($this->title->fresh()->total_on_hand)->toBe(100)
        ->and($this->title->fresh()->total_reserved)->toBe(30)
        ->and($this->ledger->availableFor($this->title->fresh()))->toBe(70);
});

it('never reserves the same copies twice', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 50, $this->user);

    $this->ledger->reserve($this->title, 40);

    expect(fn () => $this->ledger->reserve($this->title->fresh(), 11))
        ->toThrow(RuntimeException::class);

    expect($this->title->fresh()->total_reserved)->toBe(40);
});

it('spreads a reservation across sections when no single one can cover it', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 30, $this->user);
    $this->ledger->post($this->title, $this->other, StockMovementType::RECEIPT, 30, $this->user);

    $split = $this->ledger->reserve($this->title->fresh(), 45);

    expect(array_sum($split))->toBe(45)
        ->and($this->title->fresh()->total_reserved)->toBe(45);
});

it('consumes the reservation when the reserved stock is issued', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 100, $this->user);
    $this->ledger->reserve($this->title, 30);

    $this->ledger->post($this->title->fresh(), $this->section, StockMovementType::ISSUE, 30, $this->user);

    expect($this->title->fresh()->total_on_hand)->toBe(70)
        ->and($this->title->fresh()->total_reserved)->toBe(0);
});

it('releases a reservation back to availability', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 100, $this->user);
    $this->ledger->reserve($this->title, 30);

    $this->ledger->release($this->title->fresh(), 30);

    expect($this->title->fresh()->total_reserved)->toBe(0)
        ->and($this->ledger->availableFor($this->title->fresh()))->toBe(100);
});

it('flags a title at or below its reorder level', function () {
    $this->ledger->post($this->title, $this->section, StockMovementType::RECEIPT, 20, $this->user);

    expect(BookTitle::lowStock()->count())->toBe(1)
        ->and($this->title->fresh()->isLowStock())->toBeTrue();

    $this->ledger->post($this->title->fresh(), $this->section, StockMovementType::RECEIPT, 1, $this->user);

    expect(BookTitle::lowStock()->count())->toBe(0);
});
