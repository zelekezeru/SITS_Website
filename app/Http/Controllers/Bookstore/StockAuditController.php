<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\StockAudit;
use App\Models\StockAuditLine;
use App\Models\StoreRoom;
use App\Services\Bookstore\StockAuditService;
use App\Services\Bookstore\WorkflowException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Counting and verification.
 *
 * The count screen is driven by section QR scans: scanning a section while an
 * audit is open jumps straight to that section's lines (see ScanController), so
 * a counter never has to search a list on a warehouse floor.
 */
class StockAuditController extends Controller
{
    public function __construct(private readonly StockAuditService $audits)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Bookstore/Audits/Index', [
            'audits' => StockAudit::with(['storeRoom:id,name,code', 'startedBy:id,name', 'approvedBy:id,name'])
                ->withCount('lines')
                ->orderByDesc('created_at')
                ->paginate(20),
            'stores' => StoreRoom::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_room_id' => 'required|exists:store_rooms,id',
            'notes'         => 'nullable|string',
        ]);

        $audit = $this->audits->start(
            StoreRoom::findOrFail($validated['store_room_id']),
            $request->user(),
            $validated['notes'] ?? null
        );

        return redirect()->route('bookstore.audits.show', $audit)
            ->with('success', "Audit {$audit->reference} started. Scan a section QR to begin counting.");
    }

    public function show(StockAudit $audit, Request $request): Response
    {
        $audit->load(['storeRoom:id,name,code', 'startedBy:id,name', 'approvedBy:id,name']);

        $lines = $audit->lines()
            ->with(['bookTitle:id,code,title', 'shelfSection.shelf', 'countedBy:id,name'])
            ->when($request->integer('section'), fn ($q, $id) => $q->where('shelf_section_id', $id))
            ->get();

        return Inertia::render('Bookstore/Audits/Show', [
            'audit'          => $audit,
            'lines'          => $lines,
            'focusedSection' => $request->integer('section') ?: null,
            'sections'       => $audit->storeRoom->sections()->ordered()->get(['shelf_sections.id', 'shelf_sections.code', 'shelf_sections.name']),
            'summary'        => [
                'total'      => $audit->lines()->count(),
                'counted'    => $audit->counted_lines,
                'progress'   => $audit->progress,
                'variances'  => $audit->variances()->count(),
            ],
        ]);
    }

    public function count(Request $request, StockAuditLine $line): RedirectResponse
    {
        $validated = $request->validate([
            'counted_quantity' => 'required|integer|min:0',
            'note'             => 'nullable|string|max:255',
        ]);

        try {
            $this->audits->count($line, (int) $validated['counted_quantity'], $request->user(), $validated['note'] ?? null);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Count recorded.');
    }

    /** A title found where the system did not expect it. */
    public function addLine(Request $request, StockAudit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'shelf_section_id' => 'required|exists:shelf_sections,id',
            'book_title_id'    => 'required|exists:book_titles,id',
        ]);

        try {
            $this->audits->addLine($audit, (int) $validated['shelf_section_id'], (int) $validated['book_title_id']);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Line added to the count sheet.');
    }

    public function complete(Request $request, StockAudit $audit): RedirectResponse
    {
        try {
            $this->audits->complete($audit, $request->user());
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Counting complete. The variance now needs approval.');
    }

    public function approve(Request $request, StockAudit $audit): RedirectResponse
    {
        try {
            $this->audits->approve($audit, $request->user());
        } catch (WorkflowException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Variance approved and corrections posted to the ledger.');
    }

    public function cancel(Request $request, StockAudit $audit): RedirectResponse
    {
        try {
            $this->audits->cancel($audit, $request->user());
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Audit cancelled.');
    }
}
