<?php

namespace App\Services\Bookstore;

use App\Enums\StockAuditStatus;
use App\Enums\StockMovementType;
use App\Models\BookStock;
use App\Models\StockAudit;
use App\Models\StockAuditLine;
use App\Models\StoreRoom;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Physical counting and verification.
 *
 * Starting an audit freezes what the system believes; the counter walks the
 * aisle scanning section QRs and entering what is actually there; an approver
 * — a different permission — signs the variance off, and only then do
 * corrections reach the ledger.
 */
class StockAuditService
{
    public function __construct(private readonly StockLedger $ledger)
    {
    }

    /** Snapshot every title/section balance in the store room and open the count. */
    public function start(StoreRoom $storeRoom, User $actor, ?string $notes = null): StockAudit
    {
        return DB::transaction(function () use ($storeRoom, $actor, $notes) {
            $audit = StockAudit::create([
                'reference'     => StockAudit::nextReference(),
                'store_room_id' => $storeRoom->id,
                'status'        => StockAuditStatus::IN_PROGRESS,
                'started_by'    => $actor->id,
                'started_at'    => now(),
                'notes'         => $notes,
            ]);

            $sectionIds = $storeRoom->sections()->pluck('shelf_sections.id');

            BookStock::whereIn('shelf_section_id', $sectionIds)
                ->get()
                ->each(fn (BookStock $stock) => $audit->lines()->create([
                    'shelf_section_id' => $stock->shelf_section_id,
                    'book_title_id'    => $stock->book_title_id,
                    'system_quantity'  => $stock->quantity,
                ]));

            return $audit->load('lines');
        });
    }

    /** Record what was physically on the shelf. Re-counting a line overwrites it. */
    public function count(StockAuditLine $line, int $quantity, User $actor, ?string $note = null): StockAuditLine
    {
        if (! $line->stockAudit->status->acceptsCounts()) {
            throw new WorkflowException('This audit is no longer accepting counts.');
        }

        if ($quantity < 0) {
            throw new WorkflowException('A counted quantity cannot be negative.');
        }

        $line->update([
            'counted_quantity' => $quantity,
            'counted_by'       => $actor->id,
            'counted_at'       => now(),
            'note'             => $note,
        ]);

        return $line->refresh();
    }

    /**
     * A title found on a shelf that the system did not expect there — the
     * counter scans the section QR and adds the line on the spot.
     */
    public function addLine(StockAudit $audit, int $shelfSectionId, int $bookTitleId): StockAuditLine
    {
        if (! $audit->status->acceptsCounts()) {
            throw new WorkflowException('This audit is no longer accepting counts.');
        }

        $systemQuantity = (int) BookStock::where('book_title_id', $bookTitleId)
            ->where('shelf_section_id', $shelfSectionId)
            ->value('quantity');

        return $audit->lines()->firstOrCreate(
            ['shelf_section_id' => $shelfSectionId, 'book_title_id' => $bookTitleId],
            ['system_quantity' => $systemQuantity]
        );
    }

    /** Counting is finished; the variance now needs a signature. */
    public function complete(StockAudit $audit, User $actor): StockAudit
    {
        if ($audit->status !== StockAuditStatus::IN_PROGRESS) {
            throw new WorkflowException('Only an audit in progress can be completed.');
        }

        if ($audit->lines()->whereNull('counted_quantity')->exists()) {
            throw new WorkflowException('Every line must be counted before the audit can be completed.');
        }

        $audit->update([
            'status'       => StockAuditStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        return $audit->refresh();
    }

    /**
     * Accept the variance and post the corrections.
     *
     * This is the only path by which a count changes stock, and it requires the
     * approve permission — the counter cannot sign for their own discrepancy.
     */
    public function approve(StockAudit $audit, User $actor): StockAudit
    {
        if ($audit->status !== StockAuditStatus::COMPLETED) {
            throw new WorkflowException('Only a completed audit can be approved.');
        }

        return DB::transaction(function () use ($audit, $actor) {
            foreach ($audit->lines()->with(['bookTitle', 'shelfSection'])->get() as $line) {
                $variance = $line->variance;

                if (! $variance) {
                    continue;
                }

                $this->ledger->post(
                    $line->bookTitle,
                    $line->shelfSection,
                    $variance > 0 ? StockMovementType::AUDIT_SURPLUS : StockMovementType::AUDIT_SHORTAGE,
                    abs($variance),
                    $actor,
                    [
                        'reference'        => $audit,
                        'reference_number' => $audit->reference,
                        'description'      => 'Stock audit '.$audit->reference,
                        'remark'           => $line->note,
                    ]
                );

                BookStock::where('book_title_id', $line->book_title_id)
                    ->where('shelf_section_id', $line->shelf_section_id)
                    ->update(['last_counted_at' => now()]);
            }

            $audit->update([
                'status'      => StockAuditStatus::APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            return $audit->refresh();
        });
    }

    public function cancel(StockAudit $audit, User $actor): StockAudit
    {
        if ($audit->status->isFinal()) {
            throw new WorkflowException('This audit is already closed.');
        }

        $audit->update(['status' => StockAuditStatus::CANCELLED]);

        return $audit->refresh();
    }
}
