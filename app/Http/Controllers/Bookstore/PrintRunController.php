<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\BookTitle;
use App\Models\PrintRun;
use App\Models\ShelfSection;
use App\Services\Bookstore\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Receiving a printing batch — the module's only source of new stock.
 *
 * Posting a run writes a RECEIPT movement and rolls the title's weighted average
 * unit cost, so valuation stays honest across reprints at different prices.
 */
class PrintRunController extends Controller
{
    public function __construct(private readonly StockLedger $ledger)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Bookstore/PrintRuns/Index', [
            'printRuns' => PrintRun::with([
                'bookTitle:id,code,title',
                'shelfSection.shelf.storeRoom',
                'receivedBy:id,name',
            ])
                ->when($request->input('book_title_id'), fn ($q, $id) => $q->where('book_title_id', $id))
                ->orderByDesc('received_on')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('book_title_id'),
            'options' => $this->formOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Bookstore/PrintRuns/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_title_id'    => 'required|exists:book_titles,id',
            'batch_number'     => 'nullable|string|max:40|unique:print_runs,batch_number',
            'quantity'         => 'required|integer|min:1',
            'unit_cost'        => 'required|numeric|min:0',
            'printer_name'     => 'nullable|string|max:255',
            'invoice_number'   => 'nullable|string|max:60',
            'crv_number'       => 'nullable|string|max:60',
            'printed_on'       => 'nullable|date',
            'received_on'      => 'required|date',
            'shelf_section_id' => 'required|exists:shelf_sections,id',
            'notes'            => 'nullable|string',
        ]);

        $title   = BookTitle::findOrFail($validated['book_title_id']);
        $section = ShelfSection::findOrFail($validated['shelf_section_id']);

        $printRun = DB::transaction(function () use ($validated, $title, $section, $request) {
            $printRun = PrintRun::create([
                ...$validated,
                'batch_number' => $validated['batch_number'] ?: $this->nextBatchNumber($title),
                'total_cost'   => round($validated['quantity'] * (float) $validated['unit_cost'], 2),
                'received_by'  => $request->user()->id,
            ]);

            $this->ledger->post($title, $section, StockMovementType::RECEIPT, (int) $validated['quantity'], $request->user(), [
                'reference'        => $printRun,
                'reference_number' => $printRun->invoice_number ?: $printRun->batch_number,
                'unit_price'       => (float) $validated['unit_cost'],
                'description'      => 'Print run '.$printRun->batch_number,
                'remark'           => $printRun->printer_name,
                'occurred_at'      => $printRun->received_on,
            ]);

            $this->rollWeightedAverageCost($title, (int) $validated['quantity'], (float) $validated['unit_cost']);

            return $printRun;
        });

        return redirect()->route('bookstore.titles.show', $printRun->book_title_id)
            ->with('success', "Print run {$printRun->batch_number} received into {$section->code}.");
    }

    /**
     * New average = (existing stock × existing cost + new stock × new cost) / total.
     *
     * Computed on the stock that existed BEFORE this run, which is why the caller
     * posts the receipt first and passes the incoming quantity explicitly.
     */
    protected function rollWeightedAverageCost(BookTitle $title, int $quantity, float $unitCost): void
    {
        $title->refresh();

        $priorQuantity = max(0, $title->total_on_hand - $quantity);
        $priorCost     = (float) ($title->unit_cost ?? 0);
        $totalQuantity = $priorQuantity + $quantity;

        if ($totalQuantity <= 0) {
            return;
        }

        $title->update([
            'unit_cost' => round((($priorQuantity * $priorCost) + ($quantity * $unitCost)) / $totalQuantity, 2),
        ]);
    }

    protected function nextBatchNumber(BookTitle $title): string
    {
        $sequence = PrintRun::where('book_title_id', $title->id)->count() + 1;

        return $title->code.'-PR'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'titles'   => BookTitle::active()->orderBy('title')->get(['id', 'code', 'title', 'unit_cost']),
            'sections' => ShelfSection::with('shelf.storeRoom')->ordered()->get()
                ->map(fn (ShelfSection $s) => ['id' => $s->id, 'label' => $s->path]),
        ];
    }
}
