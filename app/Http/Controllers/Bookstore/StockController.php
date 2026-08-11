<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\BookStock;
use App\Models\BookTitle;
use App\Models\ShelfSection;
use App\Models\StockMovement;
use App\Models\StoreRoom;
use App\Services\Bookstore\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Stock on hand, the bin card, transfers and manual adjustments. */
class StockController extends Controller
{
    public function __construct(private readonly StockLedger $ledger)
    {
    }

    /** Where everything is, right now. */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'store_room_id', 'shelf_section_id', 'stock']);

        $stocks = BookStock::query()
            ->with(['bookTitle:id,code,title,unit_price,unit_cost,reorder_level', 'shelfSection.shelf.storeRoom'])
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->whereHas('bookTitle', fn ($sub) => $sub
                ->where('title', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")))
            ->when($filters['shelf_section_id'] ?? null, fn ($q, $id) => $q->where('shelf_section_id', $id))
            ->when($filters['store_room_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'shelfSection.shelf',
                fn ($sub) => $sub->where('store_room_id', $id)
            ))
            ->when(($filters['stock'] ?? null) === 'with', fn ($q) => $q->where('quantity', '>', 0))
            ->orderByDesc('quantity')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Bookstore/Stock/Index', [
            'stocks'  => $stocks,
            'filters' => $filters,
            'options' => [
                'stores'   => StoreRoom::active()->orderBy('name')->get(['id', 'name']),
                'sections' => ShelfSection::with('shelf.storeRoom')->ordered()->get()
                    ->map(fn (ShelfSection $s) => ['id' => $s->id, 'label' => $s->path]),
            ],
        ]);
    }

    /**
     * The bin card: one title at one section, oldest movement first, with the
     * running balance — deliberately the same shape as the paper store log so a
     * store keeper recognises it at a glance.
     */
    public function binCard(Request $request, BookTitle $title): Response
    {
        $sectionId = $request->integer('shelf_section_id') ?: null;

        $movements = StockMovement::query()
            ->forTitle($title->id)
            ->when($sectionId, fn ($q) => $q->where('shelf_section_id', $sectionId))
            ->between($request->input('from'), $request->input('to'))
            ->with(['shelfSection.shelf.storeRoom', 'performedBy:id,name'])
            ->ledgerOrder()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Bookstore/Stock/BinCard', [
            'title'     => $title->only(['id', 'code', 'title', 'unit_price', 'unit_cost']),
            'movements' => $movements,
            'filters'   => $request->only(['shelf_section_id', 'from', 'to']),
            'sections'  => $this->ledger->locationsFor($title)
                ->map(fn (BookStock $stock) => [
                    'id'       => $stock->shelf_section_id,
                    'label'    => $stock->shelfSection->path,
                    'quantity' => $stock->quantity,
                ]),
            'reconciliation' => $sectionId ? [
                'cached'     => (int) BookStock::where('book_title_id', $title->id)
                    ->where('shelf_section_id', $sectionId)
                    ->value('quantity'),
                'from_ledger' => $this->ledger->recomputeBalance($title->id, $sectionId),
            ] : null,
        ]);
    }

    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_title_id' => 'required|exists:book_titles,id',
            'from_section'  => 'required|exists:shelf_sections,id',
            'to_section'    => 'required|exists:shelf_sections,id|different:from_section',
            'quantity'      => 'required|integer|min:1',
            'note'          => 'nullable|string|max:255',
        ]);

        try {
            $this->ledger->transfer(
                BookTitle::findOrFail($validated['book_title_id']),
                ShelfSection::findOrFail($validated['from_section']),
                ShelfSection::findOrFail($validated['to_section']),
                (int) $validated['quantity'],
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock transferred.');
    }

    /**
     * A manual correction. Only the four hand-postable types are accepted —
     * receipts, issues and returns must come from their own documented flows so
     * the ledger always traces back to a print run, a waybill or a return note.
     */
    public function adjust(Request $request): RedirectResponse
    {
        $manual = array_map(fn (StockMovementType $t) => $t->value, StockMovementType::manual());

        $validated = $request->validate([
            'book_title_id'    => 'required|exists:book_titles,id',
            'shelf_section_id' => 'required|exists:shelf_sections,id',
            'type'             => 'required|string|in:'.implode(',', $manual),
            'quantity'         => 'required|integer|min:1',
            'reason'           => 'required|string|max:255',
        ]);

        try {
            $this->ledger->post(
                BookTitle::findOrFail($validated['book_title_id']),
                ShelfSection::findOrFail($validated['shelf_section_id']),
                StockMovementType::from($validated['type']),
                (int) $validated['quantity'],
                $request->user(),
                ['description' => 'Manual adjustment', 'remark' => $validated['reason']]
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Adjustment posted to the ledger.');
    }

    /** Titles at or below their reorder level — the low-stock working list. */
    public function lowStock(): Response
    {
        $titles = BookTitle::active()
            ->lowStock()
            ->with(['stocks', 'program:id,title', 'studyMode:id,name'])
            ->orderBy('title')
            ->get()
            ->map(fn (BookTitle $title) => [
                'id'               => $title->id,
                'code'             => $title->code,
                'title'            => $title->title,
                'category'         => $title->category_label,
                'on_hand'          => $title->total_on_hand,
                'reserved'         => $title->total_reserved,
                'reorder_level'    => $title->reorder_level,
                'reorder_quantity' => $title->reorder_quantity,
                'weeks_of_cover'   => $title->weeksOfCover(),
                'out_of_stock'     => $title->isOutOfStock(),
            ]);

        return Inertia::render('Bookstore/Stock/LowStock', [
            'titles' => $titles,
        ]);
    }
}
