<?php

namespace App\Services\Bookstore;

use App\Enums\BookDispatchStatus;
use App\Enums\BookRequestStatus;
use App\Enums\StockMovementType;
use App\Models\BookDispatch;
use App\Models\BookRequest;
use App\Models\BookStock;
use App\Models\BookTitle;
use App\Models\ShelfSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Releasing stock out of the store: the only place `ISSUE` movements are made.
 *
 * A dispatch may be partial — the store keeper picks what is physically on the
 * shelf today, and the request stays open for the balance.
 */
class DispatchService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly BookRequestWorkflow $workflow,
    ) {
    }

    /**
     * @param  array<int, array{book_request_item_id: int, shelf_section_id: int, quantity: int}>  $lines
     */
    public function dispatch(BookRequest $request, User $actor, array $lines, array $meta = []): BookDispatch
    {
        if (! in_array($request->status, [BookRequestStatus::APPROVED, BookRequestStatus::PARTIALLY_DISPATCHED], true)) {
            throw new WorkflowException('Only an approved request can be dispatched.');
        }

        $lines = array_values(array_filter($lines, fn (array $line) => (int) $line['quantity'] > 0));

        if ($lines === []) {
            throw new WorkflowException('Enter a quantity for at least one line.');
        }

        return DB::transaction(function () use ($request, $actor, $lines, $meta) {
            $dispatch = BookDispatch::create([
                'dispatch_number'    => BookDispatch::nextNumber(),
                'book_request_id'    => $request->id,
                'dispatched_by'      => $actor->id,
                'dispatched_at'      => now(),
                'received_by_name'   => $meta['received_by_name'] ?? $request->contact_name,
                'received_by_phone'  => $meta['received_by_phone'] ?? $request->contact_phone,
                'status'             => BookDispatchStatus::PREPARED,
                'notes'              => $meta['notes'] ?? null,
            ]);

            $items = $request->items()->with('bookTitle')->get()->keyBy('id');
            $totalQuantity = 0;
            $totalAmount   = 0.0;

            foreach ($lines as $line) {
                $item = $items->get((int) $line['book_request_item_id']);

                if (! $item) {
                    throw new WorkflowException('A dispatch line refers to a book that is not on this request.');
                }

                $quantity   = (int) $line['quantity'];
                $outstanding = $item->quantity_outstanding;

                if ($quantity > $outstanding) {
                    throw new WorkflowException(sprintf(
                        'Cannot dispatch %d of "%s" — only %d still outstanding on this request.',
                        $quantity,
                        $item->bookTitle->title,
                        $outstanding
                    ));
                }

                $section = ShelfSection::findOrFail((int) $line['shelf_section_id']);

                $this->ledger->post(
                    $item->bookTitle,
                    $section,
                    StockMovementType::ISSUE,
                    $quantity,
                    $actor,
                    [
                        'reference'        => $dispatch,
                        'reference_number' => $dispatch->dispatch_number,
                        'unit_price'       => (float) $item->unit_price,
                        'description'      => 'Issued to '.$request->destination_name,
                        'remark'           => $request->request_number,
                    ]
                );

                $lineTotal = round($quantity * (float) $item->unit_price, 2);

                $dispatch->items()->create([
                    'book_title_id'    => $item->book_title_id,
                    'shelf_section_id' => $section->id,
                    'quantity'         => $quantity,
                    'unit_price'       => $item->unit_price,
                    'line_total'       => $lineTotal,
                ]);

                $item->increment('quantity_dispatched', $quantity);

                $totalQuantity += $quantity;
                $totalAmount   += $lineTotal;
            }

            $dispatch->update([
                'total_quantity' => $totalQuantity,
                'total_amount'   => $totalAmount,
            ]);

            $this->workflow->markDispatched($request->refresh(), $actor);

            return $dispatch->load('items.bookTitle', 'items.shelfSection');
        });
    }

    /** The receiver scanned the waybill QR (or signed for it at the desk). */
    public function confirmReceipt(BookDispatch $dispatch, User $actor, array $meta = []): BookDispatch
    {
        if ($dispatch->isReceived()) {
            return $dispatch;
        }

        return DB::transaction(function () use ($dispatch, $actor, $meta) {
            $dispatch->update([
                'status'                 => BookDispatchStatus::RECEIVED,
                'received_at'            => now(),
                'received_by_user_id'    => $actor->id,
                'received_by_name'       => $meta['received_by_name'] ?? $dispatch->received_by_name,
                'received_by_phone'      => $meta['received_by_phone'] ?? $dispatch->received_by_phone,
                'receipt_signature_path' => $meta['receipt_signature_path'] ?? $dispatch->receipt_signature_path,
            ]);

            $request = $dispatch->bookRequest;

            // The request closes only when every consignment on it has landed.
            if ($request->status === BookRequestStatus::DISPATCHED
                && $request->dispatches()->where('status', '!=', BookDispatchStatus::RECEIVED->value)->doesntExist()) {
                $this->workflow->confirmReceipt($request, $actor);
            }

            return $dispatch->refresh();
        });
    }

    /**
     * Sections that can actually supply a title, for the picking screen.
     *
     * @return \Illuminate\Support\Collection<int, BookStock>
     */
    public function pickingOptions(BookTitle $title)
    {
        return $this->ledger->locationsFor($title);
    }
}
