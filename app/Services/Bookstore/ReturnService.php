<?php

namespace App\Services\Bookstore;

use App\Enums\StockMovementType;
use App\Models\BookReturn;
use App\Models\BookTitle;
use App\Models\Center;
use App\Models\ShelfSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Books coming back from a centre — the "quantity returned" column of the paper
 * issue-and-return form.
 *
 * Sound copies are re-shelved (RETURN_IN); damaged ones are received and then
 * immediately written off (DAMAGE), so the ledger shows both facts rather than
 * quietly losing the difference.
 */
class ReturnService
{
    public function __construct(private readonly StockLedger $ledger)
    {
    }

    /**
     * @param  array<int, array{book_title_id: int, quantity_returned: int, quantity_damaged?: int, remark?: string|null}>  $lines
     */
    public function record(array $attributes, array $lines, User $actor): BookReturn
    {
        $lines = array_values(array_filter($lines, fn (array $l) => (int) $l['quantity_returned'] > 0));

        if ($lines === []) {
            throw new WorkflowException('Enter a returned quantity for at least one book.');
        }

        return DB::transaction(function () use ($attributes, $lines, $actor) {
            $section = ShelfSection::findOrFail((int) $attributes['shelf_section_id']);

            $return = BookReturn::create([
                'return_number'    => BookReturn::nextNumber(),
                'book_dispatch_id' => $attributes['book_dispatch_id'] ?? null,
                'center_id'        => $attributes['center_id'] ?? null,
                'campus_id'        => $attributes['campus_id'] ?? null,
                'shelf_section_id' => $section->id,
                'returned_on'      => $attributes['returned_on'],
                'received_by'      => $actor->id,
                'returned_by_name' => $attributes['returned_by_name'] ?? null,
                'condition_note'   => $attributes['condition_note'] ?? null,
            ]);

            $total = 0;

            foreach ($lines as $line) {
                $title    = BookTitle::findOrFail((int) $line['book_title_id']);
                $returned = (int) $line['quantity_returned'];
                $damaged  = (int) ($line['quantity_damaged'] ?? 0);

                if ($damaged > $returned) {
                    throw new WorkflowException(
                        "Damaged quantity for \"{$title->title}\" cannot exceed the quantity returned."
                    );
                }

                $this->ledger->post($title, $section, StockMovementType::RETURN_IN, $returned, $actor, [
                    'reference'        => $return,
                    'reference_number' => $return->return_number,
                    'unit_price'       => (float) $title->unit_price,
                    'description'      => 'Returned from '.($return->center?->name ?? $return->campus?->name ?? 'centre'),
                    'remark'           => $line['remark'] ?? null,
                ]);

                if ($damaged > 0) {
                    $this->ledger->post($title, $section, StockMovementType::DAMAGE, $damaged, $actor, [
                        'reference'        => $return,
                        'reference_number' => $return->return_number,
                        'description'      => 'Written off on return',
                        'remark'           => $line['remark'] ?? null,
                    ]);
                }

                $return->items()->create([
                    'book_title_id'     => $title->id,
                    'quantity_returned' => $returned,
                    'quantity_damaged'  => $damaged,
                    'remark'            => $line['remark'] ?? null,
                ]);

                $total += $returned;
            }

            $return->update(['total_quantity' => $total]);

            return $return->load('items.bookTitle');
        });
    }

    /**
     * Issued − returned per title for one centre: the reconciliation the paper
     * form computes by hand, and the basis of what a centre still owes for.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function outstandingForCenter(Center $center)
    {
        $issued = DB::table('book_dispatch_items')
            ->join('book_dispatches', 'book_dispatches.id', '=', 'book_dispatch_items.book_dispatch_id')
            ->join('book_requests', 'book_requests.id', '=', 'book_dispatches.book_request_id')
            ->where('book_requests.center_id', $center->id)
            ->groupBy('book_dispatch_items.book_title_id')
            ->select('book_dispatch_items.book_title_id', DB::raw('sum(book_dispatch_items.quantity) as issued'));

        $returned = DB::table('book_return_items')
            ->join('book_returns', 'book_returns.id', '=', 'book_return_items.book_return_id')
            ->where('book_returns.center_id', $center->id)
            ->groupBy('book_return_items.book_title_id')
            ->select('book_return_items.book_title_id', DB::raw('sum(book_return_items.quantity_returned) as returned'));

        return DB::query()
            ->fromSub($issued, 'i')
            ->leftJoinSub($returned, 'r', 'r.book_title_id', '=', 'i.book_title_id')
            ->join('book_titles', 'book_titles.id', '=', 'i.book_title_id')
            ->select([
                'book_titles.id as book_title_id',
                'book_titles.code',
                'book_titles.title',
                'book_titles.unit_price',
                DB::raw('i.issued as issued'),
                DB::raw('coalesce(r.returned, 0) as returned'),
                DB::raw('i.issued - coalesce(r.returned, 0) as outstanding'),
            ])
            ->orderBy('book_titles.title')
            ->get();
    }
}
