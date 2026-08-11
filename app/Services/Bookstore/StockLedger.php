<?php

namespace App\Services\Bookstore;

use App\Enums\StockMovementType;
use App\Models\BookStock;
use App\Models\BookTitle;
use App\Models\ShelfSection;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single write path for stock.
 *
 * Nothing else in the codebase may touch `book_stocks.quantity` or insert into
 * `stock_movements`. Every change is (a) inside a transaction, (b) row-locked,
 * and (c) recorded on the bin card with the balance it produced — so a balance
 * can always be recomputed from the ledger and checked against the cache.
 */
class StockLedger
{
    /**
     * Post one movement and move the cached balance with it.
     *
     * @param  array{
     *     reference?: Model|null,
     *     reference_number?: string|null,
     *     unit_price?: float|null,
     *     description?: string|null,
     *     remark?: string|null,
     *     occurred_at?: \DateTimeInterface|string|null,
     *     allow_negative?: bool
     * }  $options
     */
    public function post(
        BookTitle $title,
        ShelfSection $section,
        StockMovementType $type,
        int $quantity,
        User $by,
        array $options = []
    ): StockMovement {
        if ($quantity <= 0) {
            throw new RuntimeException('Stock movement quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($title, $section, $type, $quantity, $by, $options) {
            $stock = $this->lockStock($title->id, $section->id);

            $delta   = $quantity * $type->sign();
            $balance = $stock->quantity + $delta;

            if ($balance < 0 && ! ($options['allow_negative'] ?? false)) {
                throw new RuntimeException(sprintf(
                    'Cannot post %s of %d for "%s" at %s: only %d on hand.',
                    $type->label(),
                    $quantity,
                    $title->title,
                    $section->code,
                    $stock->quantity
                ));
            }

            // An outbound movement consumes the reservation it was made against,
            // otherwise the reservation would keep suppressing availability for
            // stock that has already left the building.
            $reserved = $stock->reserved_quantity;
            if ($delta < 0) {
                $reserved = max(0, $reserved - $quantity);
            }

            $stock->update([
                'quantity'          => $balance,
                'reserved_quantity' => min($reserved, max(0, $balance)),
            ]);

            $unitPrice = $options['unit_price'] ?? null;
            $reference = $options['reference'] ?? null;

            return StockMovement::create([
                'book_title_id'    => $title->id,
                'shelf_section_id' => $section->id,
                'type'             => $type,
                'quantity'         => $quantity,
                'balance_after'    => $balance,
                'unit_price'       => $unitPrice,
                'total_price'      => $unitPrice === null ? null : round($unitPrice * $quantity, 2),
                'reference_type'   => $reference ? $reference->getMorphClass() : null,
                'reference_id'     => $reference?->getKey(),
                'reference_number' => $options['reference_number'] ?? null,
                'description'      => $options['description'] ?? null,
                'remark'           => $options['remark'] ?? null,
                'performed_by'     => $by->id,
                'occurred_at'      => $options['occurred_at'] ?? now(),
            ]);
        });
    }

    /** Move copies between two sections as a matched out/in pair. */
    public function transfer(
        BookTitle $title,
        ShelfSection $from,
        ShelfSection $to,
        int $quantity,
        User $by,
        ?string $note = null
    ): array {
        if ($from->is($to)) {
            throw new RuntimeException('Source and destination sections must differ.');
        }

        return DB::transaction(function () use ($title, $from, $to, $quantity, $by, $note) {
            $out = $this->post($title, $from, StockMovementType::TRANSFER_OUT, $quantity, $by, [
                'description' => 'Transfer to '.$to->path,
                'remark'      => $note,
            ]);

            $in = $this->post($title, $to, StockMovementType::TRANSFER_IN, $quantity, $by, [
                'description' => 'Transfer from '.$from->path,
                'remark'      => $note,
            ]);

            return [$out, $in];
        });
    }

    // ── Reservations ───────────────────────────────────────────────────────

    /**
     * Hold `quantity` copies of a title back from other requests, taking from
     * whichever sections have room. Returns the per-section split actually held.
     *
     * @return array<int, int>  shelf_section_id => quantity reserved
     */
    public function reserve(BookTitle $title, int $quantity, ?int $preferredSectionId = null): array
    {
        if ($quantity <= 0) {
            return [];
        }

        return DB::transaction(function () use ($title, $quantity, $preferredSectionId) {
            $rows = BookStock::where('book_title_id', $title->id)
                ->lockForUpdate()
                ->orderByRaw('shelf_section_id = ? desc', [$preferredSectionId ?? 0])
                ->orderByDesc('quantity')
                ->get();

            $available = $rows->sum(fn (BookStock $r) => max(0, $r->quantity - $r->reserved_quantity));

            if ($available < $quantity) {
                throw new RuntimeException(sprintf(
                    'Only %d of "%s" available to reserve (%d requested).',
                    $available,
                    $title->title,
                    $quantity
                ));
            }

            $remaining = $quantity;
            $split     = [];

            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }

                $free = max(0, $row->quantity - $row->reserved_quantity);
                if ($free <= 0) {
                    continue;
                }

                $take = min($free, $remaining);
                $row->update(['reserved_quantity' => $row->reserved_quantity + $take]);

                $split[$row->shelf_section_id] = $take;
                $remaining -= $take;
            }

            return $split;
        });
    }

    /** Give reserved copies back to general availability (cancel / reject). */
    public function release(BookTitle $title, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($title, $quantity) {
            $rows = BookStock::where('book_title_id', $title->id)
                ->where('reserved_quantity', '>', 0)
                ->lockForUpdate()
                ->orderByDesc('reserved_quantity')
                ->get();

            $remaining = $quantity;

            foreach ($rows as $row) {
                if ($remaining <= 0) {
                    break;
                }

                $give = min($row->reserved_quantity, $remaining);
                $row->update(['reserved_quantity' => $row->reserved_quantity - $give]);
                $remaining -= $give;
            }
        });
    }

    // ── Queries ────────────────────────────────────────────────────────────

    /** Copies a new request may claim: on hand minus everything already promised. */
    public function availableFor(BookTitle $title): int
    {
        $row = BookStock::where('book_title_id', $title->id)
            ->selectRaw('coalesce(sum(quantity), 0) as on_hand, coalesce(sum(reserved_quantity), 0) as reserved')
            ->first();

        return max(0, (int) $row->on_hand - (int) $row->reserved);
    }

    /**
     * Sections holding a title, richest first — what the dispatch screen offers
     * the store keeper to pick from.
     *
     * @return \Illuminate\Support\Collection<int, BookStock>
     */
    public function locationsFor(BookTitle $title)
    {
        return BookStock::with('shelfSection.shelf.storeRoom')
            ->where('book_title_id', $title->id)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->get();
    }

    /**
     * Recompute a section balance straight from the ledger.
     *
     * The cache should always agree with this; a disagreement is a bug or a
     * direct database edit, and the reconciliation report surfaces it.
     */
    public function recomputeBalance(int $bookTitleId, int $shelfSectionId): int
    {
        $movements = StockMovement::where('book_title_id', $bookTitleId)
            ->where('shelf_section_id', $shelfSectionId)
            ->get(['type', 'quantity']);

        return (int) $movements->sum(fn (StockMovement $m) => $m->quantity * $m->type->sign());
    }

    /** Fetch-or-create the balance row for a title/section pair, row-locked. */
    protected function lockStock(int $bookTitleId, int $shelfSectionId): BookStock
    {
        BookStock::firstOrCreate(
            ['book_title_id' => $bookTitleId, 'shelf_section_id' => $shelfSectionId],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );

        return BookStock::where('book_title_id', $bookTitleId)
            ->where('shelf_section_id', $shelfSectionId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
